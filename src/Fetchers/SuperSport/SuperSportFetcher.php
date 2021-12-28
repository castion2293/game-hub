<?php

namespace Pharaoh\GameHub\Fetchers\SuperSport;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Pharaoh\GameHub\Exceptions\GameHubException;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\GameHub\Fetchers\AbstractFetcher;

class SuperSportFetcher extends AbstractFetcher
{
    public function __construct()
    {
        $this->gameCode = 'super_sport';
    }

    /**
     *  遊戲注單撈取並轉成共用格式且存入DB
     *  執行流程:
     *    1. 設定撈單的起訖時間 (startAt, endAt)
     *    2. 執行 driver -> report 撈回遊戲原生注單
     *    3. 搜尋相對應的 遊戲注單 account 對應 平台 member_id
     *    4. 快取資料比對 (已存在快取: 過濾掉, 不存在: 寫入快取)
     *    5. 檢查原生注單 account 是否存在 members table (不存在: 過濾掉)
     *    6. 搜尋各會員的上層代理及佔成資料
     *    7. 整理整合注單格式
     *    8. 批次更新或新增注單至DB
     *    9. 寫LOG紀錄
     *
     * @param string $startAt
     * @param string $endAt
     * @param array $options
     * @return mixed
     * @throws GameHubException
     */
    public function capture(string $startAt = '', string $endAt = '', array $options = [])
    {
        try {
            // 設定撈單的起訖時間
            $this->setTimeSpan($startAt, $endAt);

            // 執行 driver -> report 撈回遊戲原生注單
            $this->fetch($options);

            // 搜尋相對應的 遊戲注單 account 對應 平台 member_id
            $memberIds = $this->findPlatformMemberIds();

            foreach ($this->rawWagers as $rawWager) {
                // 快取資料比對 (已存在快取: 過濾掉, 不存在: 寫入快取)
                if ($this->isInCache($rawWager)) {
                    continue;
                }

                // 檢查原生注單 account 是否存在 members table (不存在: 過濾掉)
                $wagerAccount = Arr::get($rawWager, 'm_id');
                if ($this->isNotInMembersTable($memberIds, $wagerAccount)) {
                    continue;
                }

                // 搜尋各會員的上層代理及佔成資料
                $memberId = Arr::get($memberIds, $wagerAccount);
                $betAt = Arr::get($rawWager, 'm_date');
                $ancestorHoldings = $this->findAncestorHoldings($memberId, $betAt);

                // 整理整合注單格式
                $this->formatWagers($memberId, $rawWager, $ancestorHoldings);
            }

            // 批次更新或新增注單至DB
            $this->updateOrCreateMulti();

            // 寫LOG紀錄
            $this->writeWagerLog(
                [
                    'game_code' => $this->gameCode,
                    'time' => $this->startAt . ' ~ ' . $this->endAt,
                    'raw_wagers' => count($this->rawWagers),
                    'wagers' => count($this->wagers),
                ]
            );

            return [
                'code' => config('api_code.success'),
                'data' => count($this->wagers),
            ];
        } catch (\Exception $exception) {
            // 寫LOG紀錄
            $this->writeWagerLog(
                [
                    'game_code' => $this->gameCode,
                    'time' => $this->startAt . ' ~ ' . $this->endAt,
                    'error_msg' => $exception->getMessage(),
                    'code_code' => $exception->getCode()
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), config('api_code.convert_error'), $exception);
        }
    }

    /**
     * 設定撈單的起訖時間 (startAt, endAt)
     *
     * @param string $startAt
     * @param string $endAt
     */
    protected function setTimeSpan(string $startAt, string $endAt)
    {
        // 沒有帶入起訖時間 就以預設的自動撈單時間為主
        if (empty($startAt) || empty($endAt)) {
            $this->startAt = now()->subDays(2)->toDateTimeString();
            $this->endAt = now()->toDateTimeString();

            return;
        }

        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    /**
     *  搜尋相對應的 遊戲注單 account 對應 平台 member_id
     *
     * @return array
     */
    protected function findPlatformMemberIds(): array
    {
        $wagerAccounts = collect($this->rawWagers)->pluck('m_id')
            ->unique()
            ->toArray();

        return DB::table('members')->select('id', 'account')
            ->whereIn('account', $wagerAccounts)
            ->get()
            ->mapWithKeys(
                function ($member) {
                    return [
                        data_get($member, 'account') => data_get($member, 'id')
                    ];
                }
            )
            ->toArray();
    }

    /**
     * 整理整合注單格式
     * 重要:
     *    如果欄位是字串格式 需使用 $this->stringWrap($string, '\'') 補上前後字符
     *    避免下階段做序列化成SQL語句後 造成寫入DB錯誤
     *
     * @param int $memberId
     * @param array $rawWager
     * @param array $ancestorHoldings
     */
    protected function formatWagers(int $memberId, array $rawWager, array $ancestorHoldings)
    {
        // 判斷注單狀態
        $status = 1;
        if (Arr::get($rawWager, 'end') === '0') {
            $status = 2; // 未結算
        }

        if (Arr::get($rawWager, 'status_note') === 'D') {
            $status = 3; // 刪除單
        }

        // 計算上層輸贏佔成結果
        $payoff = Arr::get($rawWager, 'result_gold');

        $ancestorHoldings = collect($ancestorHoldings)->map(
            function ($ancestorHolding) use ($payoff) {
                $ancestorHolding['payoff'] = 0 - $payoff * $ancestorHolding['holding'] / 100;
                $ancestorHolding['commission'] = 0;

                return $ancestorHolding;
            }
        )->toArray();

        // 處理結算時間
        $checkAt = Arr::get($rawWager, 'payout_time', '9999-12-31 00:00:00');
        if ($checkAt === '0000-00-00 00:00:00') {
            $checkAt = '9999-12-31 00:00:00';
        }

        $this->wagers[] = [
            'no' => $this->stringWrap(Arr::get($rawWager, 'sn'), '\''),
            'member_id' => $memberId,
            'game_type' => Arr::get($rawWager, 'team_no'),
            'bet_total' => Arr::get($rawWager, 'gold'),
            'bet_effective' => Arr::get($rawWager, 'bet_gold'),
            'commission' => 0,
            'payoff_none' => $payoff,
            'payoff' => $payoff,
            'status' => $status,
            'bet_at' => $this->stringWrap(Arr::get($rawWager, 'm_date'), '\''),
            'check_at' => $this->stringWrap($checkAt, '\''),
            'bet_info' => $this->stringWrap(json_encode($rawWager), '\''),
            'holding' => $this->stringWrap(json_encode($ancestorHoldings), '\'')
        ];
    }
}

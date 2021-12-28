<?php

namespace Pharaoh\GameHub\Fetchers\WmLive;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Pharaoh\GameHub\Exceptions\GameHubException;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\GameHub\Fetchers\AbstractFetcher;

class WmLiveFetcher extends AbstractFetcher
{
    public function __construct()
    {
        $this->gameCode = 'wm_live';
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
                $wagerAccount = Arr::get($rawWager, 'user');
                if ($this->isNotInMembersTable($memberIds, $wagerAccount)) {
                    continue;
                }

                // 搜尋各會員的上層代理及佔成資料
                $memberId = Arr::get($memberIds, $wagerAccount);
                $betAt = Arr::get($rawWager, 'betTime');
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
     *  搜尋相對應的 遊戲注單 account 對應 平台 member_id
     *
     * @return array
     */
    protected function findPlatformMemberIds(): array
    {
        $wagerAccounts = collect($this->rawWagers)->pluck('user')
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
        if (Arr::get($rawWager, 'reset') == 'Y' && Arr::get(gameResult, 'gameResult') == '该局取消') {
            $status = 3;
        }

        // 計算上層輸贏佔成結果
        $payoff = Arr::get($rawWager, 'winLoss');

        $ancestorHoldings = collect($ancestorHoldings)->map(
            function ($ancestorHolding) use ($payoff) {
                $ancestorHolding['payoff'] = 0 - $payoff * $ancestorHolding['holding'] / 100;
                $ancestorHolding['commission'] = 0;

                return $ancestorHolding;
            }
        )->toArray();

        $this->wagers[] = [
            'no' => Arr::get($rawWager, 'betId'),
            'member_id' => $memberId,
            'game_type' => Arr::get($rawWager, 'gid'),
            'bet_total' => Arr::get($rawWager, 'bet'),
            'bet_effective' => Arr::get($rawWager, 'validbet'),
            'commission' => 0,
            'payoff_none' => $payoff,
            'payoff' => $payoff,
            'status' => $status,
            'bet_at' => $this->stringWrap(Arr::get($rawWager, 'betTime'), '\''),
            'check_at' => $this->stringWrap(Arr::get($rawWager, 'settime', '9999-12-31 00:00:00'), '\''),
            'bet_info' => $this->stringWrap(json_encode($rawWager), '\''),
            'holding' => $this->stringWrap(json_encode($ancestorHoldings), '\'')
        ];
    }
}

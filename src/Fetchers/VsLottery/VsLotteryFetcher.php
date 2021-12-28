<?php

namespace Pharaoh\GameHub\Fetchers\VsLottery;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pharaoh\GameHub\Exceptions\GameHubException;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\GameHub\Fetchers\AbstractFetcher;

class VsLotteryFetcher extends AbstractFetcher
{
    /**
     * 遊戲帳號前綴
     *
     * @var string
     */
    private string $prefix = '';

    public function __construct()
    {
        $this->gameCode = 'vs_lottery';
        $this->prefix = config('game_hub.vs_lottery.config.prefix');
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
                $wagerAccount = Str::replaceFirst($this->prefix, '', Arr::get($rawWager, 'UserName'));
                if ($this->isNotInMembersTable($memberIds, $wagerAccount)) {
                    continue;
                }

                // 搜尋各會員的上層代理及佔成資料
                $memberId = Arr::get($memberIds, $wagerAccount);
                $betAt = Carbon::parse(Arr::get($rawWager, 'TrDate'))->toDateTimeString();
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
     * 複寫
     * 執行 driver -> report 撈回遊戲原生注單
     *
     * @param array $options
     * @throws \Pharaoh\GameHub\Exceptions\GameHubException
     */
    protected function fetch(array $options = [])
    {
        do {
            $fetchData = GameHub::driver($this->gameCode)->report($this->startAt, $this->endAt, $options);

            $code = Arr::get($fetchData, 'code');
            if ($code !== config('api_code.success')) {
                throw new \Exception("{$this->gameCode} Fetcher 撈單失敗", config("api_code.external_{$this->gameCode}_error"));
            }

            $data = Arr::get($fetchData, 'data', []);

            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $firstElement = Arr::first($data);
            if (!is_array($firstElement) && !empty($firstElement)) {
                $data = [$data];
            };

            foreach ($data as $ticket) {
                array_push($this->rawWagers, $ticket);
            }

            if (count($data) >= 100) {
                $lastFetchId = collect($data)->pluck('FetchId')
                    ->sortBy('FetchId')
                    ->last();

                // 遞迴需傳值 fromRowNo = FetchId + 1
                $options = ['fromRowNo' => $lastFetchId + 1];
                continue;
            }

            $options = [];
        } while (!empty($options));
    }

    /**
     * 搜尋相對應的 遊戲注單 account 對應 平台 member_id
     *
     * @return array
     */
    protected function findPlatformMemberIds()
    {
        $wagerAccounts = collect($this->rawWagers)->pluck('UserName')
            ->unique()
            ->map(function ($wagerAccount) {
                return Str::replaceFirst($this->prefix, '', $wagerAccount);
            })
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
        if (Arr::get($rawWager, 'IsPending') === 'true') {
            $status = 2; // 未結算
        }

        if (Arr::get($rawWager, 'IsCancelled') === 'true') {
            $status = 3; // 刪除單
        }

        // 計算上層輸贏佔成結果
        $payoff = Arr::get($rawWager, 'WinAmt') + Arr::get($rawWager, 'CommAmt');

        $ancestorHoldings = collect($ancestorHoldings)->map(
            function ($ancestorHolding) use ($payoff) {
                $ancestorHolding['payoff'] = 0 - $payoff * $ancestorHolding['holding'] / 100;
                $ancestorHolding['commission'] = 0;

                return $ancestorHolding;
            }
        )->toArray();

        // 處理結算時間
        $checkAt = Carbon::parse(Arr::get($rawWager, 'LastChangeDate'))->toDateTimeString();
        if ($status === 2) {
            $checkAt = '9999-12-31 00:00:00';
        }

        $this->wagers[] = [
            'no' => Arr::get($rawWager, 'TrDetailID'),
            'member_id' => $memberId,
            // TODO: 需跟VSL廠商要所有的gameType英文對應組
            'game_type' => 0,
            'bet_total' => Arr::get($rawWager, 'NetAmt'),
            'bet_effective' => Arr::get($rawWager, 'NetAmt'),
            'commission' => 0,
            'payoff_none' => $payoff,
            'payoff' => $payoff,
            'status' => $status,
            'bet_at' => $this->stringWrap(Carbon::parse(Arr::get($rawWager, 'TrDate'))->toDateTimeString(), '\''),
            'check_at' => $this->stringWrap($checkAt, '\''),
            'bet_info' => $this->stringWrap(json_encode($rawWager), '\''),
            'holding' => $this->stringWrap(json_encode($ancestorHoldings), '\'')
        ];
    }
}

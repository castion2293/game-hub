<?php

namespace Pharaoh\GameHub\Fetchers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\Logger\Facades\Logger;

abstract class AbstractFetcher implements FetcherInterface
{
    /**
     * 遊戲代碼
     *
     * @var string
     */
    protected string $gameCode = '';

    /**
     * 撈單開始時間
     *
     * @var string
     */
    protected string $startAt = '';

    /**
     * 撈單結束時間
     *
     * @var string
     */
    protected string $endAt = '';

    /**
     * 原生注單資料
     *
     * @var array
     */
    protected array $rawWagers = [];

    /**
     * 整合注單資料
     *
     * @var array
     */
    protected array $wagers = [];

    /**
     * 搜尋相對應的 遊戲注單 account 對應 平台 member_id
     *
     * @return array
     */
    abstract protected function findPlatformMemberIds();

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
    abstract protected function formatWagers(int $memberId, array $rawWager, array $ancestorHoldings);

    /**
     * 設定撈單的起訖時間 (startAt, endAt)
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @param string $startAt
     * @param string $endAt
     */
    protected function setTimeSpan(string $startAt, string $endAt)
    {
        // 沒有帶入起訖時間 就以預設的自動撈單時間為主
        if (empty($startAt) || empty($endAt)) {
            $this->startAt = now()->subMinutes(10)->toDateTimeString();
            $this->endAt = now()->toDateTimeString();

            return;
        }

        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    /**
     * 執行 driver -> report 撈回遊戲原生注單
     *
     * @param array $options
     * @throws \Pharaoh\GameHub\Exceptions\GameHubException
     */
    protected function fetch(array $options = [])
    {
        $fetchData = GameHub::driver($this->gameCode)->report($this->startAt, $this->endAt, $options);

        $code = Arr::get($fetchData, 'code');
        if ($code !== config('api_code.success')) {
            throw new \Exception("{$this->gameCode} Fetcher 撈單失敗", config("api_code.external_{$this->gameCode}_error"));
        }

        $this->rawWagers = Arr::get($fetchData, 'data', []);
    }

    /**
     * 快取資料比對 (已存在快取: 過濾掉, 不存在: 寫入快取)
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @param array $rawWager
     * @param int $ttl [快取存取時間(秒)]
     * @return bool
     */
    protected function isInCache(array $rawWager, int $ttl = 3600): bool
    {
        $wagerCacheKey = $this->gameCode . '-' . md5(json_encode($rawWager));

        if (Cache::has($wagerCacheKey)) {
            return true;
        }

        Cache::put($wagerCacheKey, true, $ttl);

        return false;
    }

    /**
     * 檢查原生注單 account 是否存在 members table (不存在: 過濾掉)
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @param array $memberIds
     * @param string $wagerAccount
     * @return bool
     */
    protected function isNotInMembersTable(array $memberIds, string $wagerAccount): bool
    {
        if (!isset($memberIds[$wagerAccount])) {
            return true;
        }

        return false;
    }

    /**
     * 搜尋各會員的上層代理及佔成資料
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @param int $memberId
     * @param string $betAt
     * @return array
     */
    protected function findAncestorHoldings(int $memberId, string $betAt): array
    {
        return ancestor_holdings($memberId, $this->gameCode, $betAt);
    }

    /**
     * 批次更新或新增注單至DB
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @return bool
     */
    protected function updateOrCreateMulti()
    {
        // 沒有需要轉換的注單直接返回
        if (empty($this->wagers)) {
            return true;
        }

        $fields = array_keys(Arr::first($this->wagers));

        $sql = '';
        foreach ($this->wagers as $wager) {
            $sql .= (($sql != '') ? ', ' : '') . '(' . implode(', ', array_values($wager)) . ")";
        }

        $duplicate = '';
        foreach ($fields as $field) {
            $duplicate .= ($duplicate != '') ? ', ' : '';
            $duplicate .= $field . ' = VALUES(' . $field . ')';
        }

        $table = 'external_wager_' . $this->gameCode;
        $sqlField = implode(' ,', $fields);

        DB::statement(" ALTER TABLE `" . $table . "` AUTO_INCREMENT = 1");
        return DB::statement(
            "INSERT INTO `" . $table . "` (" . $sqlField . ") VALUES " . $sql . " ON DUPLICATE KEY UPDATE " . $duplicate . " ;"
        );
    }

    /**
     * 寫注單 LOG 紀錄
     * 如有需要 可提供繼承的 Fetcher 做複寫使用
     *
     * @param array $logs
     * @param string $type
     */
    protected function writeWagerLog(array $logs, string $type = 'info')
    {
        Logger::$type('crontab', json_encode($logs));
    }

    /**
     * 字串前後加上某個字符
     *
     * @param string $value
     * @param string $wrap
     * @return string
     */
    protected function stringWrap(string $value, string $wrap): string
    {
        return Str::of($value)->start($wrap)->finish($wrap);
    }
}

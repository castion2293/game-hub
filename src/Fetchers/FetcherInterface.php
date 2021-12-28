<?php

namespace Pharaoh\GameHub\Fetchers;

use Pharaoh\GameHub\Exceptions\GameHubException;

interface FetcherInterface
{
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
    public function capture(string $startAt = '', string $endAt = '', array $options = []);
}

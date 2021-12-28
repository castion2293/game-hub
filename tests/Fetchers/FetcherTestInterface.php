<?php

namespace Pharaoh\GameHub\Tests\Fetchers;

interface FetcherTestInterface
{
    public function setUp();

    /**
     * 測試 自動撈單 不帶入特定時間
     */
    public function testAutoFetch();

    /**
     * 測試 手動撈單 帶入特定時間
     */
    public function testManualFetch();

    /**
     * 測試 部署 queue job 做撈單
     * @see \Pharaoh\GameHub\Jobs\FetchWagerJob::handle
     */
    public function testFetchWagerJob();
}

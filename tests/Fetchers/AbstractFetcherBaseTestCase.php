<?php

namespace Pharaoh\GameHub\Tests\Fetchers;

use Illuminate\Support\Facades\Cache;
use Pharaoh\GameHub\Tests\BaseTestCase;
use Pharaoh\GameHub\Tests\SeederTestCaseTrait;

abstract class AbstractFetcherBaseTestCase extends BaseTestCase implements FetcherTestInterface
{
    use SeederTestCaseTrait;

    /**
     * 遊戲代碼
     *
     * @var string
     */
    protected string $gameCode = '';

    public function setUp(): void
    {
        parent::setUp();

        // 清空快取
        Cache::flush();
    }

    //============================================================================================
    // 測試相關 function
    //============================================================================================

    /**
     * 初始化遊戲測試
     *
     * @param string $gameCode
     */
    public function initialGame(string $gameCode)
    {
        $this->gameCode = $gameCode;
    }
}

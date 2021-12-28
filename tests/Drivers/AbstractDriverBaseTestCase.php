<?php

namespace Pharaoh\GameHub\Tests\Drivers;

use Pharaoh\GameHub\Tests\BaseTestCase;
use Pharaoh\GameHub\Tests\SeederTestCaseTrait;

abstract class AbstractDriverBaseTestCase extends BaseTestCase implements DriverTestInterface
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

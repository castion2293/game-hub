<?php

namespace Pharaoh\GameHub\Tests\Driver\VsLottery;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\GameHub\Tests\Drivers\AbstractDriverBaseTestCase;

class VsLotteryDriverTest extends AbstractDriverBaseTestCase
{
    /**
     * 會員ID
     *
     * @var int
     */
    protected int $id = 1;

    /**
     * 會員帳號
     *
     * @var string
     */
    protected string $account = 'GC1103';

    public function setUp(): void
    {
        parent::setUp();

        // 初始化遊戲測試
        $this->initialGame('vs_lottery');
    }

    /**
     * 測試 註冊會員
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSignup()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // 建立會員遊戲設定資料
        $this->createMemberGameSetting($this->id);

        // Act
        $responseData = GameHub::driver($this->gameCode)->signup($this->member, $this->memberGameSetting);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 檢查是否有註冊
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testCheckSignup()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // Act
        $responseData = GameHub::driver($this->gameCode)->checkSignup($this->member);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 取得登入網址
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testEnterGame()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
            'lang' => 'zh-TW',
        ];

        // Act
        $responseData = GameHub::driver($this->gameCode)->enterGame($this->member);
        $this->console->writeln($responseData);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue(filter_var($data, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * 測試 額度轉入遊戲
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testDeposit()
    {
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        $amount = 10;
        $tradeNo = Str::random(16);

        // Act
        $responseData = GameHub::driver($this->gameCode)->deposit($this->member, $amount, $tradeNo);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 額度轉出遊戲
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testWithdraw()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        $amount = 10;
        $tradeNo = Str::random(16);

        // Act
        $responseData = GameHub::driver($this->gameCode)->withdraw($this->member, $amount, $tradeNo);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 檢查轉帳狀態
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testCheckTransfer()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        $amount = 10;
        $tradeNo = Str::random(16);

        // 執行一筆轉帳
        GameHub::driver($this->gameCode)->deposit($this->member, $amount, $tradeNo);

        // Act
        $responseData = GameHub::driver($this->gameCode)->checkTransfer($this->member, $tradeNo);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 會員額度
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testBalance()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // Act
        $responseData = GameHub::driver($this->gameCode)->balance($this->member);
        $this->console->writeln($responseData);

        // Assert
        $code = Arr::get($responseData, 'code');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertArrayHasKey('data', $responseData);
    }

    /**
     * 測試 踢除會員
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testKickUser()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // Act
        $responseData = GameHub::driver($this->gameCode)->kickUser($this->member);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 修改會員狀態
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSetStatus()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // 建立會員遊戲設定資料
        $this->createMemberGameSetting($this->id);

        // 修改會員遊戲狀態設定 (1: 開啟, 0: 關閉)
        $this->memberGameSetting['switch'] = 1;

        // Act
        $responseData = GameHub::driver($this->gameCode)->setStatus($this->member, $this->memberGameSetting);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertTrue($data);
    }

    /**
     * 測試 修改會員範本
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSetModel()
    {
        // Arrange
        // 建立種子會員
        $this->member = [
            'id' => $this->id,
            'account' => $this->account,
        ];

        // 建立會員遊戲設定資料
        $this->createMemberGameSetting($this->id);

        // 修改會員遊戲範本
        $this->memberGameSetting['model'] = "A";

        // Act
        $responseData = GameHub::driver($this->gameCode)->setModel($this->member, $this->memberGameSetting);

        // Assert
        $code = Arr::get($responseData, 'code');
        $data = Arr::get($responseData, 'data');

        $this->assertEquals($code, config('api_code.no_such_function'));
        $this->assertFalse($data);
    }

    /**
     * 測試 查詢下注紀錄
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testReport()
    {
        // Arrange
        $startAt = now()->startOfDay()->toDateTimeString();
        $endAt = now()->endOfDay()->toDateTimeString();

        // Act
        $responseData = GameHub::driver($this->gameCode)->report($startAt, $endAt);

        // Assert
        $code = Arr::get($responseData, 'code');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertArrayHasKey('data', $responseData);
    }
}

<?php

namespace Pharaoh\GameHub\Tests\Drivers;

interface DriverTestInterface
{
    public function setUp();

    /**
     * 測試 註冊會員
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSignup();

    /**
     * 測試 檢查是否有註冊
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testCheckSignup();

    /**
     * 測試 取得登入網址
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testEnterGame();

    /**
     * 測試 額度轉入遊戲
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testDeposit();

    /**
     * 測試 額度轉出遊戲
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testWithdraw();

    /**
     * 測試 檢查轉帳狀態
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testCheckTransfer();

    /**
     * 測試 會員額度
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testBalance();

    /**
     * 測試 踢除會員
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testKickUser();

    /**
     * 測試 修改會員狀態
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSetStatus();

    /**
     * 測試 修改會員範本
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testSetModel();

    /**
     * 測試 查詢下注紀錄
     * @see \Pharaoh\GameHub\Drivers\
     */
    public function testReport();
}

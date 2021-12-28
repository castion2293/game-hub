<?php

namespace Pharaoh\GameHub\Drivers;

interface DriverInterface
{
    /**
     * 檢查是否有註冊
     *
     * @param array $member
     * @param array $options
     * @return array
     */
    public function checkSignup(array $member, array $options = []): array;

    /**
     * 註冊會員
     *
     * @param array $member [會員基本資料]
     * @param array $memberGameSetting [會員遊戲設定]
     * @param array $options [其他項目」
     * @return array
     */
    public function signup(array $member, array $memberGameSetting, array $options = []): array;

    /**
     * 取得登入網址
     *
     * @param array $member
     * @param array $options
     * @return array
     */
    public function enterGame(array $member, array $options = []): array;

    /**
     * 額度轉入遊戲
     *
     * @param array $member
     * @param $amount
     * @param string $tradeNo
     * @param array $options
     * @return array
     */
    public function deposit(array $member, $amount, string $tradeNo, array $options = []): array;

    /**
     * 額度轉出遊戲
     *
     * @param array $member
     * @param $amount
     * @param string $tradeNo
     * @param array $options
     * @return array
     */
    public function withdraw(array $member, $amount, string $tradeNo, array $options = []): array;

    /**
     * 檢查轉帳狀態
     *
     * @param array $member
     * @param string $tradeNo
     * @param array $options
     * @return array
     */
    public function checkTransfer(array $member, string $tradeNo, array $options = []): array;

    /**
     * 會員額度
     *
     * @param array $member
     * @param array $options
     * @return array
     */
    public function balance(array $member, array $options = []): array;

    /**
     * 踢除會員
     *
     * @param array $member
     * @param array $options
     * @return array
     */
    public function kickUser(array $member, array $options = []): array;

    /**
     * 修改會員狀態
     *
     * @param array $member
     * @param array $memberGameSetting
     * @param array $options
     * @return array
     */
    public function setStatus(array $member, array $memberGameSetting, array $options = []): array;

    /**
     * 修改會員範本
     *
     * @param array $member
     * @param array $memberGameSetting
     * @param array $options
     * @return array
     */
    public function setModel(array $member, array $memberGameSetting, array $options = []): array;

    /**
     * 查詢下注紀錄
     *
     * @param string $startAt
     * @param string $endAt
     * @param array $options
     * @return array
     */
    public function report(string $startAt, string $endAt, array $options = []): array;
}

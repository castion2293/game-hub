<?php

namespace Pharaoh\GameHub\Tests;

use Pharaoh\GameHub\Tests\Models\Member;

trait SeederTestCaseTrait
{
    /**
     * 種子會員
     *
     * @var array
     */
    protected array $member = [];

    /**
     * 會員遊戲設定資料
     *
     * @var array
     */
    protected array $memberGameSetting = [];

    //============================================================================================
    // 建種子相關 function
    //============================================================================================

    /**
     * 建立 member model 資料
     *
     * @param string $account
     */
    protected function createMemberModel(string $account)
    {
        $member = new Member();
        $member->account = $account;
        $member->save();

        $this->member = $member->toArray();
    }

    /**
     * 建立會員遊戲設定資料
     *
     * @param int $memberId
     */
    protected function createMemberGameSetting(int $memberId)
    {
        $defaultGameSetting = config("game_setting.{$this->gameCode}");

        $this->memberGameSetting = array_merge(
            [
                'member_id' => $memberId
            ],
            $defaultGameSetting
        );
    }
}

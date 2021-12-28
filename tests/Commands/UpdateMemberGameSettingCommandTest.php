<?php

namespace Pharaoh\GameHub\Tests\Commands;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Pharaoh\GameHub\Tests\BaseTestCase;

class UpdateMemberGameSettingCommandTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 測試 更新會員遊戲設定
     * @see \Pharaoh\GameHub\Commands\UpdateMemberGameSettingCommand
     */
    public function testUpdateMemberGameSetting()
    {
        // Arrange
        $gameCode = 'wm_live';
        // 建立會員設定資料
        $this->createMemberGameSetting();

        // Act
        Artisan::call(
            'game-hub:update-member-game-setting',
            [
                'game_code' => $gameCode,
            ]
        );

        // Assert
        $defaultSettings = config("game_setting.{$gameCode}");

        $memberGameSetting = (array)DB::table('member_game_setting')
            ->select('switch', 'model', 'win_limit', 'bet_limit')
            ->where('member_id', 1)
            ->get()
            ->map(function ($memberGameSetting) {
                $settings = [];

                foreach ($memberGameSetting as $field => $value) {
                    $settings[$field] = json_decode($value, true);
                }

                return $settings;
            })
            ->first();

        foreach ($defaultSettings as $field => $defaultSetting) {
            $this->assertEquals($defaultSetting, Arr::get($memberGameSetting, "{$field}.{$gameCode}"));
        }
    }

    /**
     * 建立會員設定資料
     */
    private function createMemberGameSetting()
    {
        for ($i = 0; $i < 3; $i++) {
            $memberGameSettings = [];

            $chunk = 10000;
            for ($j = 0; $j < $chunk; $j++) {
                $memberGameSettings[] = [
                    'member_id' => $i * $chunk + ($j + 1),
                    'enter_game' => json_encode([]),
                    'switch' => json_encode([]),
                    'model' => json_encode([]),
                    'win_limit' => json_encode([]),
                    'bet_limit' => json_encode([])
                ];
            }

            DB::table('member_game_setting')->insert($memberGameSettings);
        }
    }
}

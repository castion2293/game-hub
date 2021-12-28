<?php

namespace Pharaoh\GameHub\Tests\Commands;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Pharaoh\GameHub\Jobs\FetchWagerJob;
use Pharaoh\GameHub\Tests\BaseTestCase;

class FetchWagerCommandTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        // 清空快取
        Cache::flush();
    }

    /**
     * 測試 指定 特定日期 及 特定遊戲 做手動撈單
     * @see \Pharaoh\GameHub\Commands\FetchWagerCommand
     */
    public function testFetchWagerWithDateAndGameCode()
    {
        $this->markTestIncomplete();

        // Arrange
        $date = now()->toDateString();
        $gameCode = 'wm_live';

        // Act
        Artisan::call(
            'game-hub:fetch-wager',
            [
                '--date' => $date,
                '--game_code' => $gameCode
            ]
        );
    }

    /**
     * 測試 指定 特定日期 手動撈所有遊戲注單
     * @see \Pharaoh\GameHub\Commands\FetchWagerCommand
     */
    public function testFetchWagerWithDate()
    {
        $this->markTestIncomplete();

        // Arrange
        // 建立所有的 game kind
        $this->createAllGameKinds();

        $date = now()->toDateString();

        // Act
        Artisan::call(
            'game-hub:fetch-wager',
            [
                '--date' => $date
            ]
        );
    }

    /**
     * 測試 使用 queue job 自動撈所有遊戲注單
     * @see \Pharaoh\GameHub\Commands\FetchWagerCommand
     */
    public function testFetchWagerUsingQueue()
    {
        // Arrange
        // 建立所有的 game kind
        $this->createAllGameKinds();

        Queue::fake();

        // Act
        Artisan::call(
            'game-hub:fetch-wager',
            [
                '--queue' => true
            ]
        );

        // Assert
        Queue::assertPushed(FetchWagerJob::class);
    }

    /**
     * 測試 自動撈所有遊戲注單
     * @see \Pharaoh\GameHub\Commands\FetchWagerCommand
     */
    public function testFetchWager()
    {
        // Arrange
        // 建立所有的 game kind
        $this->createAllGameKinds();

        // Act
        Artisan::call('game-hub:fetch-wager');
    }

    /**
     * 建立所有的 game kind
     */
    private function createAllGameKinds()
    {
        $gameKinds = collect(config('game_hub'))->map(function ($gameKind, $gameCode) {
            return [
                'code' => $gameCode,
                'name' => Arr::get($gameKind, 'name'),
            ];
        })->toArray();


        DB::table('game_kind')
            ->insert($gameKinds);
    }
}

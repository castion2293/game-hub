<?php

namespace Pharaoh\GameHub\Tests\Fetchers\SuperSport;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Pharaoh\GameHub\Facades\GameHub;
use Pharaoh\GameHub\Jobs\FetchWagerJob;
use Pharaoh\GameHub\Tests\Fetchers\AbstractFetcherBaseTestCase;

class SuperSportFetcherTest extends AbstractFetcherBaseTestCase
{
    /**
     * 會員帳號
     *
     * @var string
     */
    protected string $account = 'GC1101';

    public function setUp(): void
    {
        parent::setUp();

        // 初始化遊戲測試
        $this->initialGame('super_sport');

        // 建立 member model 資料
        $this->createMemberModel($this->account);
    }

    /**
     * 測試 自動撈單 不帶入特定時間
     */
    public function testAutoFetch()
    {
        // Act
        $result = GameHub::fetcher($this->gameCode)->capture();

        // Assert
        $code = Arr::get($result, 'code');
        $data = Arr::get($result, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertGreaterThanOrEqual(0, $data);

        if ($data > 0) {
            $this->assertDatabaseHas(
                'external_wager_' . $this->gameCode,
                [
                    'member_id' => $this->member['id']
                ]
            );
        }
    }

    /**
     * 測試 手動撈單 帶入特定時間
     */
    public function testManualFetch()
    {
        // Arrange
        $startAt = Carbon::parse('2021-07-13')->startOfDay()->toDateTimeString();
        $endAt = Carbon::parse('2021-07-13')->endOfDay()->toDateTimeString();

        // Act
        $result = GameHub::fetcher($this->gameCode)->capture($startAt, $endAt);

        // Assert
        $code = Arr::get($result, 'code');
        $data = Arr::get($result, 'data');

        $this->assertEquals($code, config('api_code.success'));
        $this->assertGreaterThanOrEqual(0, $data);

        if ($data > 0) {
            $this->assertDatabaseHas(
                'external_wager_' . $this->gameCode,
                [
                    'member_id' => $this->member['id']
                ]
            );
        }
    }

    /**
     * 測試 部署 queue job 做撈單
     * @see \Pharaoh\GameHub\Jobs\FetchWagerJob::handle
     */
    public function testFetchWagerJob()
    {
        // Arrange
        $options = [];

        Queue::fake();

        // Act
        GameHub::dispatch($this->gameCode, $options);

        // Assert
        Queue::assertPushed(FetchWagerJob::class, 1);
        Queue::assertPushed(
            function (FetchWagerJob $job) {
                return $job->gameCode === $this->gameCode;
            }
        );
    }
}

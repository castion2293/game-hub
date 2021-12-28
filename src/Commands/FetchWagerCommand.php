<?php

namespace Pharaoh\GameHub\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Pharaoh\GameHub\Facades\GameHub;

class FetchWagerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-hub:fetch-wager 
        {--date= : 指定日期} 
        {--game_code= : 指定遊戲代碼}
        {--queue : 指定使用 queue job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '撈取注單指令';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $date = $this->option('date');
        $gameCode = $this->option('game_code');

        $isFetchWagerWithDateAndGameCode = !empty($date) && !empty($gameCode);
        if ($isFetchWagerWithDateAndGameCode) {
            // 指定 特定日期 及 特定遊戲 做手動撈單
            $startAt = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $endAt = Carbon::parse($date)->endOfDay()->toDateTimeString();

            try {
                $result = GameHub::fetcher($gameCode)->capture($startAt, $endAt);
                $this->info("指定 特定日期:{$date} 及 遊戲:{$gameCode} 做手動撈單 完成");
                $this->newLine('1');
                $this->line("轉換單數: {$result['data']}");
            } catch (\Exception $exception) {
                $this->error("指定 特定日期:{$date} 及 遊戲:{$gameCode} 做手動撈單 失敗");
                $this->newLine('1');
                $this->line("錯誤訊息: {$exception->getMessage()}");
            }

            return 0;
        }

        $isFetchWagerWithDate = !empty($date) && empty($gameCode);
        if ($isFetchWagerWithDate) {
            // 指定 特定日期 手動撈所有遊戲注單
            $startAt = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $endAt = Carbon::parse($date)->endOfDay()->toDateTimeString();

            $gameCodes = $this->getAllGameCodes();

            foreach ($gameCodes as $gameCode) {
                try {
                    $result = GameHub::fetcher($gameCode)->capture($startAt, $endAt);
                    $this->info("指定 特定日期:{$date} 及 遊戲:{$gameCode} 做手動撈單 完成");
                    $this->newLine('1');
                    $this->line("轉換單數: {$result['data']}");
                } catch (\Exception $exception) {
                    $this->error("指定 特定日期:{$date} 及 遊戲:{$gameCode} 做手動撈單 失敗");
                    $this->newLine('1');
                    $this->line("錯誤訊息: {$exception->getMessage()}");
                    continue;
                }
            }

            return 0;
        }

        $queue = $this->option('queue') ?? false;
        if ($queue) {
            // 使用 queue job 自動撈所有遊戲注單
            $gameCodes = $this->getAllGameCodes();

            foreach ($gameCodes as $gameCode) {
                try {
                    GameHub::dispatch($gameCode);
                    $this->info("使用 queue job 遊戲:{$gameCode} 自動撈單 完成");
                } catch (\Exception $exception) {
                    $this->info("使用 queue job 遊戲:{$gameCode} 自動撈單 失敗");
                    continue;
                }
            }

            return 0;
        }

        // 自動撈所有遊戲注單
        // 使用 queue job 自動撈所有遊戲注單
        $gameCodes = $this->getAllGameCodes();

        foreach ($gameCodes as $gameCode) {
            try {
                $result = GameHub::fetcher($gameCode)->capture();
                $this->info("遊戲:{$gameCode} 自動撈單 完成");
                $this->newLine('1');
                $this->line("轉換單數: {$result['data']}");
            } catch (\Exception $exception) {
                $this->info("遊戲:{$gameCode} 自動撈單 失敗");
                $this->newLine('1');
                $this->line("錯誤訊息: {$exception->getMessage()}");
                continue;
            }
        }

        return 0;
    }

    /**
     * 獲取所有遊戲代碼
     */
    private function getAllGameCodes(): array
    {
        // 狀態(1:啟用, 2:停用, 4:維護)
        $active = [1, 2, 4];

        return DB::table('game_kind')
            ->select('code')
            ->whereIn('active', $active)
            ->get()
            ->pluck('code')
            ->toArray();
    }
}

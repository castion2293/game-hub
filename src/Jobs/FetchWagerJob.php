<?php

namespace Pharaoh\GameHub\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Pharaoh\GameHub\Facades\GameHub;

class FetchWagerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 遊戲代碼
     *
     * @var string
     */
    public string $gameCode;

    /**
     * 其他設定
     *
     * @var array
     */
    public array $options;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $gameCode, array $options = [])
    {
        $this->gameCode = $gameCode;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        GameHub::fetcher($this->gameCode)->capture('', '', $this->options);
    }
}

<?php

namespace Pharaoh\GameHub\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMemberGameSettingCommand extends Command
{
    const SETTING_FIELDS = ['switch', 'model', 'win_limit', 'bet_limit'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-hub:update-member-game-setting 
        {game_code}
        {--omit : 不存在新增、存在則略過不作處理}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新會員遊戲設定';

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
        try {
            DB::transaction(
                function () {
                    $gameCode = $this->argument('game_code');
                    $omit = $this->option('omit') ?? false;

                    $defaultSetting = config("game_setting.{$gameCode}");
                    if (empty($defaultSetting)) {
                        throw new \Exception("{$gameCode} 在 game_setting config 未設定");
                    }

                    $defaultGameSetting = collect($defaultSetting)->map(
                        function ($default) use ($gameCode) {
                            return [
                                $gameCode => $default
                            ];
                        }
                    )->toArray();

                    $this->line('開始更新會員遊戲設定');

                    $bar = $this->output->createProgressBar(DB::table('member_game_setting')->count());
                    $bar->start();

                    DB::table('member_game_setting')->select(array_merge(self::SETTING_FIELDS, ['id']))
                        ->chunkById(
                            10000,
                            function ($memberGameSettings) use ($defaultGameSetting, $omit, &$bar) {
                                $memberGameSettings = collect($memberGameSettings)->mapWithKeys(
                                    function ($memberGameSetting) use ($defaultGameSetting, $omit) {
                                        $settings = collect(self::SETTING_FIELDS)->mapWithKeys(
                                            function ($field) use ($memberGameSetting) {
                                                return [$field => json_decode(data_get($memberGameSetting, $field), true)];
                                            }
                                        )->map(
                                            function ($memberGameSettingValue, $field) use ($defaultGameSetting, $omit) {
                                                // 不存在新增、存在則略過不作處理
                                                if ($omit) {
                                                    // 會員設定 後蓋前 預設設定 不存在則會新增
                                                    return array_merge($defaultGameSetting[$field], $memberGameSettingValue);
                                                }

                                                // 預設設定 後蓋前 會員設定 存在則覆寫 不存在則會新增
                                                return array_merge($memberGameSettingValue, $defaultGameSetting[$field]);
                                            }
                                        )->toArray();

                                        return [
                                            data_get($memberGameSetting, 'id') => $settings
                                        ];
                                    }
                                )->toArray();

                                $sql = [];
                                foreach (self::SETTING_FIELDS as $field) {
                                    $sql[$field] = '';
                                }

                                foreach ($memberGameSettings as $id => $memberGameSetting) {
                                    foreach ($memberGameSetting as $field => $memberGameSettingValue) {
                                        $sql[$field] .= " WHEN " . $id . " THEN '" . json_encode($memberGameSettingValue) . "' ";
                                    }
                                }

                                $ids = implode(',', array_keys($memberGameSettings));
                                foreach ($sql as $field => $value) {
                                    DB::statement(
                                        'UPDATE `member_game_setting` SET ' . $field . ' = (CASE `id` ' . $value . ' END) WHERE id IN (' . $ids . ') '
                                    );
                                }

                                $bar->advance(10000);
                            }
                        );

                    $bar->finish();
                    $this->newLine(1);
                    $this->info("更新會員遊戲設定 成功");
                }
            );
        } catch (\Exception $exception) {
            $this->error("更新會員遊戲設定 失敗");
            $this->newLine(1);
            $this->line($exception->getMessage());
        }

        return 0;
    }
}

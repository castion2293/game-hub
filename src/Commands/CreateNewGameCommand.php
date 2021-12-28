<?php

namespace Pharaoh\GameHub\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CreateNewGameCommand extends Command
{
    const ACTIVE_ENABLE = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-hub:create-new-game';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '上線新遊戲';

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
                    $game['code'] = $this->ask('輸入遊戲代碼:(ex:wm_live)');

                    $gameHubs = config('game_hub');
                    if (!isset($gameHubs[$game['code']])) {
                        throw new \Exception("{$game['code']} 在 game_hub config 未設定");
                    }

                    $game['name'] = Arr::get($gameHubs, "{$game['code']}.name");

                    $game['active'] = $this->ask('遊戲狀態(1:啟用, 2:停用, 3:敬請期待, 4:維護, 5:下架)', 1);

                    $game['lobby'] = $this->ask('是否有遊戲大廳(Y/N)', 'Y') === 'Y';

                    $needResetSort = $this->ask('是否將此遊戲排在第一順位(Y/N)', 'Y');

                    $categories = DB::table('game_category')
                        ->select('id', 'name')
                        ->where('active', self::ACTIVE_ENABLE)
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray();

                    $category = $this->choice('遊戲類別', $categories);
                    $categoryId = Arr::get(array_flip($categories), $category);

                    DB::table('game_kind')->insert($game);

                    $gameKindId = DB::getPdo()->lastInsertId();

                    DB::table('game_kind_category')->insert(
                        [
                            'game_kind_id' => $gameKindId,
                            'game_category_id' => $categoryId
                        ]
                    );

                    // 重新排序遊戲
                    if ($needResetSort === 'Y') {
                        DB::table('game_kind')
                            ->where('code', '<>', $game['code'])
                            ->update(['sort' => DB::raw('sort + 1')]);
                    }

                    // 更新 member_game_setting
                    Artisan::call(
                        'game-hub:update-member-game-setting',
                        [
                            'game_code' => $game['code']
                        ]
                    );

                    $this->newLine(1);
                    $this->info("新增遊戲 {$game['name']} 成功");
                }
            );
        } catch (\Exception $exception) {
            $this->error("新增遊戲失敗");
            $this->newLine(1);
            $this->line($exception->getMessage());
        }

        return 0;
    }
}

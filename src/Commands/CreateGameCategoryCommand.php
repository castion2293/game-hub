<?php

namespace Pharaoh\GameHub\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateGameCategoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game-hub:create-game-category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '建立新的遊戲類別';

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
            DB::transaction(function () {
                $category['name'] = $this->ask('名稱');

                $category['active'] = $this->ask('狀態(1:啟用, 2:停用)', 1);

                $needResetSort = $this->ask('是否將此遊戲類別排在第一順位(Y/N)', 'Y');

                DB::table('game_category')->insert($category);

                // 重新排序遊戲
                if ($needResetSort === 'Y') {
                    DB::table('game_category')
                        ->where('name', '<>', $category['name'])
                        ->update(['sort' => DB::raw('sort + 1')]);
                }

                $this->info("新增遊戲類別 {$category['name']} 成功");
            });
        } catch (\Exception $exception) {
            $this->error("新增遊戲類別失敗");
            $this->newLine(1);
            $this->line($exception->getMessage());
        }

        return 0;
    }
}

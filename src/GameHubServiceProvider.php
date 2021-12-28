<?php

namespace Pharaoh\GameHub;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class GameHubServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 合併套件migration
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes(
            [
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ],
            'game-hub-database'
        );

        // 合併套件設定檔
        $this->mergeConfigFrom(__DIR__ . '/../config/game_hub.php', 'game_hub');
        $this->mergeConfigFrom(__DIR__ . '/../config/game_setting.php', 'game_setting');
        $this->mergeConfigFrom(__DIR__ . '/../config/game_model.php', 'game_model');
        $this->mergeConfigFrom(__DIR__ . '/../config/api_code.php', 'api_code');

        $this->publishes([__DIR__ . '/../config/game_hub.php' => config_path('game_hub.php')], 'game-hub-config');
        $this->publishes(
            [__DIR__ . '/../config/game_setting.php' => config_path('game_setting.php')],
            'game-hub-config'
        );
        $this->publishes([__DIR__ . '../config/game_model.php' => config_path('game_model.php')], 'game-hub-config');

        $this->commands(
            [
                \Pharaoh\GameHub\Commands\CreateNewGameCommand::class,
                \Pharaoh\GameHub\Commands\FetchWagerCommand::class,
                \Pharaoh\GameHub\Commands\CreateGameCategoryCommand::class,
                \Pharaoh\GameHub\Commands\UpdateMemberGameSettingCommand::class,
            ]
        );
    }

    public function register()
    {
        parent::register();

        $loader = AliasLoader::getInstance();
        $loader->alias('game_hub', 'Pharaoh\GameHub\GameHub');
    }
}

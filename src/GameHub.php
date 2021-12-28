<?php

namespace Pharaoh\GameHub;

use Illuminate\Support\Str;
use Pharaoh\GameHub\Exceptions\GameHubException;
use Pharaoh\GameHub\Jobs\FetchWagerJob;

class GameHub
{
    /**
     * 初始化遊戲驅動器
     *
     * @param string $gameCode
     * @return mixed|Pharaoh\\GameHub\\Drivers\\{$gameDriverFolder}\\{$gameDriverFolder}Driver
     * @throws GameHubException
     */
    public function driver(string $gameCode)
    {
        try {
            $configDriver = array_keys(config('game_hub'));
            if (!in_array($gameCode, $configDriver)) {
                throw new \Exception("{$gameCode} 在 game_hub config 未設定", config('api_code.notFound'));
            }

            // 設定 game_hub driver
            $gameDriverFolder = Str::studly($gameCode);

            return \App::make("Pharaoh\\GameHub\\Drivers\\{$gameDriverFolder}\\{$gameDriverFolder}Driver");
        } catch (\Exception $exception) {
            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 初始化遊戲注單抓取器
     *
     * @param string $gameCode
     * @return mixed|Pharaoh\\GameHub\\Fetchers\\{$gameFetcherFolder}\\{$gameFetcherFolder}Fetcher
     * @throws GameHubException
     */
    public function fetcher(string $gameCode)
    {
        try {
            $configDriver = array_keys(config('game_hub'));
            if (!in_array($gameCode, $configDriver)) {
                throw new \Exception("{$gameCode} 在 game_hub config 未設定", config('api_code.notFound'));
            }

            // 設定 game_hub fetcher
            $gameFetcherFolder = Str::studly($gameCode);

            return \App::make("Pharaoh\\GameHub\\Fetchers\\{$gameFetcherFolder}\\{$gameFetcherFolder}Fetcher");
        } catch (\Exception $exception) {
            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 部署 queue job 做撈單
     *
     * @param string $gameCode
     * @param array $options
     * @throws GameHubException
     */
    public function dispatch(string $gameCode, array $options = [])
    {
        try {
            dispatch(new FetchWagerJob($gameCode, $options))->onQueue('game-hub');
        } catch (\Exception $exception) {
            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 獲取該遊戲的遊戲種類
     *
     * @param string $gameCode
     * @return array
     * @throws GameHubException
     */
    public function gameTypes(string $gameCode): array
    {
        try {
            $filePathName = __DIR__ . '/../public/game_types/' .$gameCode . '.php';

            if (!file_exists($filePathName)) {
                throw new \Exception($filePathName . ' 檔案不存在');
            }

            return include $filePathName;
        } catch (\Exception $exception) {
            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}

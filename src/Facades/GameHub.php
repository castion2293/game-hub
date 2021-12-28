<?php

namespace Pharaoh\GameHub\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static driver(string $gameCode)
 * @method static fetcher(string $gameCode)
 * @method static dispatch(string $gameCode, array $options = [])
 * @method static gameTypes(string $gameCode)
 *
 * @see \Pharaoh\GameHub\GameHub
 */
class GameHub extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // 回傳 alias 的名稱
        return 'game_hub';
    }
}

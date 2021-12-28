<?php
/*
|--------------------------------------------------------------------------
| 各遊戲館 - 遊戲別 - 玩法配置檔，與其金鑰
|--------------------------------------------------------------------------
| 格式說明
| '遊戲代碼' => [
|   'name' => 遊戲名稱,
|   'api_url' => 遊戲 API URL 設置(string),
|   'wager_url' => 注單詳細查詢連結(string)
|   'config' => API 各環境設置，其裡面參數會依遊戲提供而有所不同(array),
| ]
|
*/

return [
    'wm_live' => [
        'name' => '完美真人',
        'api_url' => env('WM_LIVE_API_URL', 'https://api.a45.me/api/public/Gateway.php'),
        'wager_url' => '',
        'config' => [
            'vendor_id' => env('WM_LIVE_VENDOR_ID', 'j20twapi'),
            'signature' => env('WM_LIVE_SIGNATURE', '949e89d817ced85ddff4dde9c8f18f44'),
        ],
    ],
    'super_sport' => [
        'name' => 'Super體育',
        'api_url' => env('SUPER_SPORT_API_URL'),
        'wager_url' => '',
        'config' => [
            'api_key' => env('SUPER_SPORT_API_KEY') ,
            'api_iv' => env('SUPER_SPORT_API_IV'),
            'agent_account' => env('SUPER_SPORT_AGENT_ACCOUNT'),
            'agent_password' => env('SUPER_SPORT_AGENT_PASSWORD'),
        ],
    ],
    'vs_lottery' => [
        'name' => 'VSL越南彩',
        'api_url' => env('VS_LOTTERY_API_URL'),
        'wager_url' => '',
        'config' => [
            'partner_id' => env('VS_LOTTERY_PARTNER_ID'),
            'partner_password' => env('VS_LOTTERY_PARTNER_PASSWORD'),
            'currency' => env('VS_LOTTERY_CURRENCY'),
            'prefix' => env('VS_LOTTERY_PREFIX')
        ],
    ],
];

<?php

/**
 * 遊戲範本
 * 資料格式說明：
 * '遊戲代碼' => [
 *     'A' => '範本A數值',
 *     'B' => '範本B數值',
 *     'C' => '範本C數值',
 *     'D' => '範本D數值',
 *     'E' => '範本E數值',
 * ]
 */

return [
    'wm_live' => [
        'A' => env('WM_LIVE_MODEL_A'),
        'B' => env('WM_LIVE_MODEL_B'),
        'C' => env('WM_LIVE_MODEL_C'),
        'D' => env('WM_LIVE_MODEL_D'),
        'E' => env('WM_LIVE_MODEL_E'),
    ],
    'super_sport' => [
        'A' => env('SUPER_SPORT_MODEL_A'),
        'B' => env('SUPER_SPORT_MODEL_B'),
        'C' => env('SUPER_SPORT_MODEL_C'),
        'D' => env('SUPER_SPORT_MODEL_D'),
        'E' => env('SUPER_SPORT_MODEL_E'),
    ],
    'vs_lottery' => [],
];

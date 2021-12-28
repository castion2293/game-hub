<?php

/**
 * 遊戲設定初始值
 * 資料格式說明：
 * '遊戲代碼' => [
 *     switch：遊戲狀態  - 1：啟用、2：停用、3：停押
 *     model： 遊戲範本  - 範本_名稱(EX.A、B、C...)
 *     win_limit：限贏金額 [max：最大贏額 => 0為無限制 ]
 *     bet_limit：限輸金額 [min：最低投注額 => 0為無限制 ]
 * ]
 */

return [
    'wm_live' => [
        'switch' => 1,
        'model' => 'C',
        'win_limit' => [],
        'bet_limit' => [],
    ],
    'super_sport' => [
        'switch' => 1,
        'model' => 'C',
        'win_limit' => [],
        'bet_limit' => [],
    ],
    'vs_lottery' => [
        'switch' => 1,
        'model'      => '',
        'win_limit'  => [],
        'bet_limit'  => [],
    ],
];

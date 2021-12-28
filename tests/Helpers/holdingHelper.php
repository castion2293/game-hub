<?php

if (!function_exists('ancestor_holdings')) {
    /**
     * 搜尋各會員的上層代理及佔成資料 方法
     * 內容只是示意 實際操作內容需至專案中自己實踐
     */
    function ancestor_holdings(int $memberId, string $gameCode, string $betAt, array $options = []): array
    {
        return [
            [
                'layer' => 1,
                'member_id' => 11,
                'holding' => 90,
            ],
            [
                'layer' => 2,
                'member_id' => 12,
                'holding' => 80,
            ],
            [
                'layer' => 3,
                'member_id' => 13,
                'holding' => 70,
            ],
        ];
    }
}

# 遊戲API串接模組

## 安裝
使用 composer 做安裝
```bash
composer require thoth-pharaoh/game-hub
```

Migrate 資料表
```bash
php artisan migrate
```

添加 .env 遊戲必要環境參數
```bash
# ------------------
#  WM完美真人
# ------------------
WM_LIVE_API_URL="https://api.a45.me/api/public/Gateway.php"
WM_LIVE_VENDOR_ID="j20twapi"
WM_LIVE_SIGNATURE="949e89d817ced85ddff4dde9c8f18f44"
# 範本
WM_LIVE_MODEL_A="124,125,9,126,127,128,129,149,131,150,250,251,584"
WM_LIVE_MODEL_B="124,125,9,126,127,128,129,149,131,150,250,251,584"
WM_LIVE_MODEL_C="124,125,9,126,127,128,129,149,131,150,250,251,584"
WM_LIVE_MODEL_D="124,125,9,126,127,128,129,149,131,150,250,251,584"
WM_LIVE_MODEL_E="124,125,9,126,127,128,129,149,131,150,250,251,584"
```

需要在主專案 config/logger.php 中 log_folders 欄位添加 `crontab` 及 `所有的遊戲代碼`
```bash
// Log Folders 設置
'log_folders' => [
    'crontab',
    'wm_live',
    ...
],
```

## 使用方法

## 使用 Facade:

先引入門面
```
use Pharaoh\GameHub\Facades\GameHub;
```

### driver(遊戲驅動器):

- 註冊會員
```bash
GameHub::driver($gameCode)->signup($member, $memberGameSetting);
```
| 參數 | 說明 | 類型 | 範例 |
| ------------|:----------------------- | :------| :------|
| $gameCode | 遊戲代碼 | string | wm_live |
| $member | 會員資料 | array | 依據專案內容定義 |
| $memberGameSetting | 會員遊戲設定 | array | 依據專案內容定義 |

- 檢查是否有註冊
```bash
GameHub::driver($gameCode)->checkSignup($member);
```

- 取得登入網址
```bash
GameHub::driver($gameCode)->enterGame($member);
```

- 額度轉入遊戲
```bash
GameHub::driver($gameCode)->deposit($member, $amount, $tradeNo);
```
| 參數 | 說明 | 類型 | 範例 |
| ------------|:----------------------- | :------| :------|
| $amount | 額度 | float | 10.0 |
| $tradeNo | 交易流水號 | string | ABC1234 |

- 額度轉出遊戲
```bash
GameHub::driver($gameCode)->withdraw($member, $amount, $tradeNo);
```

- 檢查轉帳狀態
```bash
GameHub::driver($gameCode)->checkTransfer($member, $tradeNo);
```

- 會員額度
```bash
GameHub::driver($gameCode)->balance($member);
```

- 踢除會員
```bash
GameHub::driver($gameCode)->kickUser($member);
```

- 修改會員狀態
```bash
GameHub::driver($gameCode)->setStatus($member, $memberGameSetting);
```

- 修改會員範本
```bash
GameHub::driver($gameCode)->setModel($member, $memberGameSetting);
```

### fetcher(注單抓取器):

- 自動撈單 不帶入特定時間
```bash
GameHub::fetcher($gameCode)->capture();
```

- 手動撈單 帶入特定時間
```bash
GameHub::fetcher($gameCode)->capture($startAt, $endAt, $options);
```
| 參數 | 說明 | 類型 | 範例 |
| ------------|:----------------------- | :------| :------|
| $startAt | 撈單開始時間 | string | 2021-07-07 00:00:00 |
| $tradeNo | 撈單結束時間 | string | 2021-07-07 23:59:59 |
| $options | 其他參數(非必要) | array | [] |

> 各遊戲起訖時間限制不同

### dispatch(注單抓取任務部署):

- 部署 queue job 做撈單任務
```bash
GameHub::dispatch($gameCode, $options);
```

### gameTypes(獲取該遊戲的遊戲種類):

- 獲取該遊戲的遊戲種類
```bash
GameHub::gameTypes($gameCode);
```


## 使用 Command:

#### 建立新的遊戲類別
```bash
php artisan game-hub:create-game-category
```

#### 上線新遊戲
```bash
php artisan game-hub:create-new-game
```

#### 更新會員遊戲設定

- 強制更新或新增
```bash
php artisan game-hub:update-member-game-setting {game_code}
```
| 參數 | 說明  | 範例 |
| ------------|:----------------------- | :------|
| game_code | 遊戲代碼 | wm_live |

- 不存在新增、存在則略過不作處理
```bash
php artisan game-hub:update-member-game-setting {game_code} --omit
```

#### 撈取注單指令

- 指定 特定日期 及 特定遊戲 做手動撈單
```bash
php artisan game-hub:fetch-wager --date={date} --game_code={game_code}
```
| 參數 | 說明  | 範例 |
| ------------|:----------------------- | :------|
| date | 日期 | 2021-07-08 |
| game_code | 遊戲代碼 | wm_live |

- 指定 特定日期 手動撈所有遊戲注單
```bash
php artisan game-hub:fetch-wager --date={date}
```

- 使用 queue job 自動撈所有遊戲注單 任務部署
```bash
php artisan game-hub:fetch-wager --queue
```

需另外開啟撈單線程
```bash
php artisan queue:work --queue=game-hub --max-jobs=200 --max-time=3600 --sleep=3
```
- 自動撈所有遊戲注單
```bash
php artisan game-hub:fetch-wager
```







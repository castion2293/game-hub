<?php

namespace Pharaoh\GameHub\Drivers\SuperSport;

use Illuminate\Support\Arr;
use Pharaoh\GameHub\Drivers\AbstractDriver;
use Pharaoh\GameHub\Exceptions\GameHubException;

class SuperSportDriver extends AbstractDriver
{
    /**
     * 語系代碼轉換
     */
    private array $languages = [
        'zh-TW' => 'ZH-TW',
        'zh-CH' => 'ZH-CN',
        'vi' => 'VI'
    ];

    private string $apiKey = '';
    private string $apiIv = '';

    public function __construct()
    {
        parent::__construct();

        $this->gameCode = 'super_sport';
        $this->apiUrl = config("game_hub.{$this->gameCode}.api_url");
        $this->apiKey = config("game_hub.{$this->gameCode}.config.api_key");
        $this->apiIv = config("game_hub.{$this->gameCode}.config.api_iv");
        $this->config = [
            'up_account' => $this->encrypt(config("game_hub.{$this->gameCode}.config.agent_account")),
            'up_passwd' => $this->encrypt(config("game_hub.{$this->gameCode}.config.agent_password"))
        ];
    }

    /**
     * 檢查是否有註冊
     *
     * @param array $member
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function checkSignup(array $member, array $options = []): array
    {
        try {
            $this->parameters = array_merge(
                [
                    'act' => 'search',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'level' => 1
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/account',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 註冊會員
     *
     * @param array $member [會員基本資料]
     * @param array $memberGameSetting [會員遊戲設定]
     * @param array $options [其他項目」
     * @return array
     * @throws GameHubException
     */
    public function signup(array $member, array $memberGameSetting, array $options = []): array
    {
        try {
            $model = config("game_model.{$this->gameCode}.{$memberGameSetting['model']}", 'C');

            $this->parameters = array_merge(
                [
                    'act' => 'cpAdd',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'passwd' => $this->encrypt(Arr::get($member, 'account')),
                    'nickname' => Arr::get($member, 'account'),
                    'level' => 1,
                    'copy_target' => $model,
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/account',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 取得登入網址
     *
     * @param array $member
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function enterGame(array $member, array $options = []): array
    {
        try {
            $lang = Arr::get($member, 'lang');

            $this->parameters = [
                'account' => $this->encrypt(Arr::get($member, 'account')),
                'passwd' => $this->encrypt(Arr::get($member, 'account')),
                'responseFormat' => 'json',
                'lang' => Arr::get($this->languages, $lang, 'ZH-TW')
            ];

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/login',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 額度轉入遊戲
     *
     * @param array $member
     * @param $amount
     * @param string $tradeNo
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function deposit(array $member, $amount, string $tradeNo, array $options = []): array
    {
        try {
            $this->parameters = array_merge(
                [
                    'act' => 'add',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'point' => $amount,
                    'track_id' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/points',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 額度轉出遊戲
     *
     * @param array $member
     * @param $amount
     * @param string $tradeNo
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function withdraw(array $member, $amount, string $tradeNo, array $options = []): array
    {
        try {
            $this->parameters = array_merge(
                [
                    'act' => 'sub',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'point' => $amount,
                    'track_id' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/points',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 檢查轉帳狀態
     *
     * @param array $member
     * @param string $tradeNo
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function checkTransfer(array $member, string $tradeNo, array $options = []): array
    {
        try {
            $this->parameters = array_merge(
                [
                    'act' => 'checking',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'track_id' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/points',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 會員額度
     *
     * @param array $member
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function balance(array $member, array $options = []): array
    {
        try {
            $this->parameters = array_merge(
                [
                    'act' => 'search',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/points',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 踢除會員
     *
     * @param array $member
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function kickUser(array $member, array $options = []): array
    {
        try {
            $this->parameters = [
                'account' => $this->encrypt(Arr::get($member, 'account')),
            ];

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/logout',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 修改會員狀態
     *
     * @param array $member
     * @param array $memberGameSetting
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function setStatus(array $member, array $memberGameSetting, array $options = []): array
    {
        try {
            // Super體育 狀態選項 (0:啟用 1:停用)
            $superSportStatus = [
                1 => 0,
                0 => 1
            ];

            $this->parameters = array_merge(
                [
                    'act' => 'mdy',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'level' => 1,
                    'old_password' => $this->encrypt(Arr::get($member, 'account')),
                    'allowed_playing' => Arr::get($superSportStatus, $memberGameSetting['switch'], 0)
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/account',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 修改會員範本
     *
     * @param array $member
     * @param array $memberGameSetting
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function setModel(array $member, array $memberGameSetting, array $options = []): array
    {
        try {
            $model = config("game_model.{$this->gameCode}.{$memberGameSetting['model']}", 'C');

            $this->parameters = array_merge(
                [
                    'act' => 'cpSettings',
                    'account' => $this->encrypt(Arr::get($member, 'account')),
                    'level' => 1,
                    'copy_target' => $model
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/account',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            // 寫 API Log 紀錄
            $this->writeApiLog(
                [
                    'response' => [
                        'error_msg' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]
                ],
                'error'
            );

            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 查詢下注紀錄
     *
     * @param string $startAt
     * @param string $endAt
     * @param array $options
     * @return array
     * @throws GameHubException
     */
    public function report(string $startAt, string $endAt, array $options = []): array
    {
        try {
            $start = explode(' ', $startAt);
            $end = explode(' ', $endAt);

            $this->parameters = [
                'act' => 'detail',
                'account' => Arr::get($this->config, 'up_account'),
                'level' => 2,
                's_date' => $start[0],
                'e_date' => $end[0],
                'start_time' => $start[1],
                'end_time' => $end[1],
            ];

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl . '/report',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'request_type' => 'form_params',
                    'timeout' => 300
                ]
            );

            return $this->handleResponseData($responseData);
        } catch (\Exception $exception) {
            throw new GameHubException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * 處理回傳資料訊息
     *
     * @param array $responseData
     * @return array
     * @throws \Exception
     */
    protected function handleResponseData(array $responseData)
    {
        if (Arr::get($responseData, 'code') !== 999) {
            throw new \Exception(Arr::get($responseData, 'msg'), config('api_code.external_super_sport_error'));
        }

        $functionName = Arr::get(debug_backtrace(), '1.function');
        $data = Arr::get($responseData, 'data');

        if (method_exists($this, $formatFunction = $functionName . 'format')) {
            $this->$formatFunction($data);
        }

        // 寫 API Log 紀錄
        $this->writeApiLog(
            [
                'response' => $responseData
            ]
        );

        return $this->formatSuccessResponse($functionName, $data);
    }

    /**
     * AES-128 模式加密傳送的資料
     *
     * @param $value
     * @return string
     */
    private function encrypt($value): string
    {
        return openssl_encrypt($value, "AES-128-CBC", $this->apiKey, 0, $this->apiIv);
    }

    /**
     * 檢查轉帳狀態 API 回傳整理
     *
     * @param $data
     * @throws \Exception
     */
    private function checkTransferFormat(&$data)
    {
        if (Arr::get($data, 'result') != 1) {
            throw new \Exception('查詢操作紀錄結果: 無此紀錄', config('api_code.external_super_sport_error'));
        }
    }

    /**
     * 取得登入網址 API 回傳整理
     *
     * @param $data
     */
    private function enterGameFormat(&$data)
    {
        $data = Arr::get($data, 'login_url');
    }

    /**
     * 會員額度 API 回傳整理
     *
     * @param $data
     */
    private function balanceFormat(&$data)
    {
        $data = floatval(Arr::get($data, 'point'));
    }
}

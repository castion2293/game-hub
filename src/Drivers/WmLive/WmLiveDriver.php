<?php

namespace Pharaoh\GameHub\Drivers\WmLive;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Pharaoh\GameHub\Drivers\AbstractDriver;
use Pharaoh\GameHub\Exceptions\GameHubException;

class WmLiveDriver extends AbstractDriver
{
    /**
     * 語系代碼轉換
     */
    private array $languages = [
        'zh-TW' => 9,
        'zh-CH' => 0,
        'vi' => 3
    ];

    public function __construct()
    {
        parent::__construct();

        $this->gameCode = 'wm_live';
        $this->apiUrl = config("game_hub.{$this->gameCode}.api_url");
        $this->config = [
            'vendorId' => config("game_hub.{$this->gameCode}.config.vendor_id"),
            'signature' => config("game_hub.{$this->gameCode}.config.signature"),
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
                    'cmd' => 'GetBalance',
                    'user' => Arr::get($member, 'account')
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
     * @param array $member
     * @param array $memberGameSetting
     * @param array $options
     * @return array
     * @throws GameHubException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function signup(array $member, array $memberGameSetting, array $options = []): array
    {
        try {
            $model = config("game_model.{$this->gameCode}.{$memberGameSetting['model']}", 'C');

            $this->parameters = array_merge(
                [
                    'cmd' => 'MemberRegister',
                    'user' => Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'username' => Arr::get($member, 'account'),
                    'limitType' => $model,
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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

            $this->parameters = array_merge(
                [
                    'cmd' => 'SigninGame',
                    'user' => Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'lang' => Arr::get($this->languages, $lang, 9),
                    'voice' => Arr::get($this->languages, $lang, 9),
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
                    'cmd' => 'ChangeBalance',
                    'user' => Arr::get($member, 'account'),
                    'money' => $amount,
                    'order' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
                    'cmd' => 'ChangeBalance',
                    'user' => Arr::get($member, 'account'),
                    'money' => 0 - $amount,
                    'order' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
                    'cmd' => 'GetMemberTradeReport',
                    'user' => Arr::get($member, 'account'),
                    'order' => $tradeNo
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
                    'cmd' => 'GetBalance',
                    'user' => Arr::get($member, 'account')
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
            $this->parameters = array_merge(
                [
                    'cmd' => 'LogoutGame',
                    'user' => Arr::get($member, 'account')
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
            // WM 狀態選項 (Y:啟用 N:停用)
            $wmLiveStatus = [
                1 => 'Y',
                0 => 'N'
            ];

            $this->parameters = array_merge(
                [
                    'cmd' => 'EnableorDisablemem',
                    'user' => Arr::get($member, 'account'),
                    'type' => 'login',
                    'status' => Arr::get($wmLiveStatus, $memberGameSetting['switch'], 'Y')
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
                    'cmd' => 'EditLimit',
                    'user' => Arr::get($member, 'account'),
                    'limitType' => $model
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
            $this->parameters = array_merge(
                [
                    'cmd' => 'GetDateTimeReport',
                    'timetype' => 0, // 0: 抓下注時間 1: 抓結算時間
                    'datatype' => 0, // 0: 輸贏報表 1: 小費報表
                    'startTime' => Carbon::parse($startAt)->format("YmdHis"),
                    'endTime' => Carbon::parse($endAt)->format("YmdHis")
                ],
                $this->config
            );

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'application/json'],
                    'request_type' => 'query',
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
        if (Arr::get($responseData, 'errorCode') !== 0) {
            throw new \Exception(Arr::get($responseData, 'errorMessage'), config('api_code.external_wm_live_error'));
        }

        // 寫 API Log 紀錄
        $this->writeApiLog(
            [
                'response' => $responseData
            ]
        );

        return $this->formatSuccessResponse(
            Arr::get(debug_backtrace(), '1.function'),
            Arr::get($responseData, 'result')
        );
    }
}

<?php

namespace Pharaoh\GameHub\Drivers\VsLottery;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Pharaoh\GameHub\Drivers\AbstractDriver;
use Pharaoh\GameHub\Exceptions\GameHubException;

class VsLotteryDriver extends AbstractDriver
{
    /**
     * 遊戲 API 方法名稱
     *
     * @var string
     */
    private string $methodName = '';

    /**
     * 遊戲的帳號前綴
     *
     * @var string
     */
    private string $prefix = '';

    /**
     * 語系代碼轉換
     *
     * @var string[]
     */
    private array $languages = [
        'zh-TW' => 'zh-Hant',
        'zh-CH' => 'zh-Hans',
        'vi' => 'vi-VN'
    ];

    /**
     * 遊戲相關錯誤碼
     *
     * @var string[]
     */
    private array $errorCodes = [
        5100001 => 'Partner is not available (wrong Partner ID, wrong Password or wrong UserName)',
        5100002 => 'Invalid currency',
        5100003 => 'System error',
        5100004 => 'User already exists',
        5100014 => 'Wrong username/password',
        5100015 => 'System not available',
        5100019 => 'Account is locked',
        5100104 => 'System error',
        5100235 => 'Invalid amount',
        5100441 => 'Deposit/withdraw transaction is failed',
        5100368 => 'Member does not have enough money to withdraw.',
        5100369 => 'Invalid user name or user not found',
        5100370 => 'Duplicate Deposit/withdraw transaction ID OR Calling more than once deposit/withdraw for same player within 5 seconds'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->gameCode = 'vs_lottery';
        $this->apiUrl = config("game_hub.{$this->gameCode}.api_url");
        $this->config = [
            'partnerId' => config("game_hub.{$this->gameCode}.config.partner_id"),
            'partnerPassword' => config("game_hub.{$this->gameCode}.config.partner_password")
        ];
        $this->prefix = config('game_hub.vs_lottery.config.prefix');
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account')
                ],
                $this->config
            );

            $this->methodName = 'GetPlayerBalance';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'lastName' => Arr::get($member, 'account'),
                    'currencyCode' => config("game_hub.{$this->gameCode}.config.currency")
                ],
                $this->config
            );
            $this->methodName = 'CreatePlayerAccount';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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

            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'lang' => Arr::get($this->languages, $lang, 'zh-Hant')
                ],
                $this->config
            );
            $this->methodName = 'GetLoginUrl';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'amount' => $amount,
                    'clientRefTransId' => $tradeNo
                ],
                $this->config
            );
            $this->methodName = 'DepositWithdrawRef';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                    'password' => Arr::get($member, 'account'),
                    'amount' => 0 - $amount,
                    'clientRefTransId' => $tradeNo
                ],
                $this->config
            );
            $this->methodName = 'DepositWithdrawRef';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = ['clientRefTransId' => $tradeNo];
            $this->methodName = 'CheckDepositWithdrawStatus';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                ],
                $this->config
            );
            $this->methodName = 'GetPlayerBalance';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                ],
                $this->config
            );
            $this->methodName = 'KickOutPlayer';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
            $arrayParameters = array_merge(
                [
                    'userName' => $this->prefix . Arr::get($member, 'account'),
                    'isAllowLogin' => $memberGameSetting['switch']
                ],
                $this->config
            );
            $this->methodName = 'SetAllowLogin';

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
     */
    public function setModel(array $member, array $memberGameSetting, array $options = []): array
    {
        return [
            'code' => config('api_code.no_such_function'),
            'data' => false
        ];
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
            $arrayParameters = array_merge(
                [
                    'fromDate' => Carbon::parse($startAt)->format('Y-m-d\TH:i:s'),
                    'toDate' => Carbon::parse($endAt)->format('Y-m-d\TH:i:s'),
                ],
                $this->config
            );
            $this->methodName = 'GetBetTransaction';

            if (Arr::has($options, 'fromRowNo')) {
                $arrayParameters = array_merge($arrayParameters, $options);
            }

            // array 轉換成 xml 格式
            $this->parameters = $this->getSoapXml($arrayParameters);

            $responseData = $this->httpRequest(
                [
                    'method' => 'post',
                    'submit_url' => $this->apiUrl,
                    'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                    'request_type' => 'body',
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
     * 複寫
     * Http API 請求
     * 如有需要 可提供繼承的 Driver 做複寫使用
     *
     * @param array $attributes
     * 格式說明:
     *   'method' => 請求方法(ex: get, post)
     *   'submit_url => 請求地址
     *   'headers' => 請求標頭(ex: ['Content-Type' => 'application/json'])
     *   'request_type' => 請求格式(ex: json, query, form_params)
     *   'timeout' => 請求逾時時間 單位(秒)
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function httpRequest(array $attributes = [])
    {
        $method = Arr::get($attributes, 'method', 'get');
        $submitUrl = Arr::get($attributes, 'submit_url', '');
        $headers = Arr::get($attributes, 'headers', []);
        $requestType = Arr::get($attributes, 'request_type', 'json');
        $timeout = Arr::get($attributes, 'timeout', 10);

        // 寫 API Log 紀錄
        $this->writeApiLog(
            [
                'method' => $method,
                'url' => $submitUrl,
                'parameter' => $this->parameters
            ]
        );

        $response = $this->guzzleClient->request(
            $method,
            $submitUrl,
            [
                'headers' => $headers,
                $requestType => $this->parameters,
                'timeout' => $timeout
            ]
        );

        return $this->xmlToArray($response->getBody()->getContents());
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
        if (($result = Arr::get($responseData, 'result')) !== 0) {
            throw new \Exception(Arr::get($this->errorCodes, $result), config('api_code.external_vs_lottery_error'));
        }

        $functionName = Arr::get(debug_backtrace(), '1.function');

        $data = [];
        if (method_exists($this, $formatFunction = $functionName . 'format')) {
            $data = $this->$formatFunction($responseData);
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
     * array 轉換成 xml 格式
     *
     * @param string $methodName
     * @param array $parameter
     * @return string
     */
    private function getSoapXml(array $parameters): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                    <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                        <soap12:Body><' . $this->methodName . ' xmlns="http://www.universal.ws/webservices">';

        foreach ($parameters as $key => $value) {
            $xml .= "<{$key}>{$value}</{$key}>";
        }

        $xml .= "</{$this->methodName}></soap12:Body></soap12:Envelope>";

        return $xml;
    }

    /**
     * xml 資料轉成 array
     * @param string $xml
     * @param string $methodName
     * @return mixed
     */
    private function xmlToArray(string $xml)
    {
        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'xs:', 'xml:', 'msdata:'], '', $xml);
        $xmlData = simplexml_load_string($clean_xml);
        $arrayData = json_decode(json_encode((array)$xmlData, JSON_NUMERIC_CHECK), true);
        $arrayData = Arr::get(Arr::get($arrayData, 'Body'), $this->methodName . 'Response');

        $arrayData['result'] = Arr::get($arrayData, $this->methodName . 'Result');
        unset($arrayData[$this->methodName . 'Result']);

        if ($this->methodName === 'GetBetTransaction') {
            $arrayData['result'] = Arr::get($arrayData, 'errorCode');
        }

        return $arrayData;
    }

    /**
     * 取得登入網址 API 回傳整理
     *
     * @param array $responseData
     * @return array|\ArrayAccess|mixed
     */
    private function enterGameFormat(array $responseData)
    {
        return Arr::get($responseData, 'url');
    }

    /**
     * 會員額度 API 回傳整理
     *
     * @param array $responseData
     * @return array|\ArrayAccess|mixed
     */
    private function balanceFormat(array $responseData)
    {
        return Arr::get($responseData, 'balance');
    }

    /**
     * 注單資料 API 回傳整理
     *
     * @param array $responseData
     * @return array|\ArrayAccess|mixed
     */
    private function reportFormat(array $responseData)
    {
        return Arr::get($responseData, 'trans.Trans.TransactionDetail', []);
    }
}

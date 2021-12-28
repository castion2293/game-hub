<?php

namespace Pharaoh\GameHub\Drivers;

use Illuminate\Support\Arr;
use GuzzleHttp\Client as GuzzleClient;
use Pharaoh\Logger\Facades\Logger;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * 遊戲代碼
     *
     * @var string
     */
    protected $gameCode = '';

    /**
     *  遊戲 API URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * 注單詳細查詢連結
     *
     * @var string
     */
    protected $wagerUrl = '';

    /**
     * 遊戲API基本設定
     *
     * @var array
     */
    protected $config = [];

    /**
     * 遊戲API參數
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * HTTP 請求器
     *
     * @var GuzzleClient
     */
    protected $guzzleClient;

    public function __construct()
    {
        $this->guzzleClient = new GuzzleClient();
    }

    /**
     * 處理回傳資料訊息
     *
     * @param array $responseData
     * @return array
     * @throws \Exception
     */
    abstract protected function handleResponseData(array $responseData);

    /**
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

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 規範成功回傳的格式內容
     *
     * @param string $functionName
     * @param $data
     * @return array
     */
    protected function formatSuccessResponse(string $functionName, $data): array
    {
        $needReturnDataFunctions = ['enterGame', 'balance', 'report'];

        if (in_array($functionName, $needReturnDataFunctions)) {
            return [
                'code' => config('api_code.success'),
                'data' => $data
            ];
        }

        return [
            'code' => config('api_code.success'),
            'data' => true
        ];
    }

    /**
     * 寫 API Log 紀錄
     *
     * @param array $logs
     * @param string $type
     */
    protected function writeApiLog(array $logs, string $type = 'info')
    {
        // 撈單 API report 不需要寫 Log
        $functions = Arr::pluck(debug_backtrace(), 'function');
        if (in_array('report', $functions)) {
            return;
        }

        Logger::$type($this->gameCode, json_encode($logs));
    }
}

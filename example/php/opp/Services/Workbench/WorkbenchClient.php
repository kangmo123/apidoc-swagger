<?php

namespace App\Services\Workbench;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WorkbenchClient
{
    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appKey;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * WorkbenchClient constructor.
     */
    public function __construct()
    {
        $config = config('services.merchant');
        $name = $config['name'] ?? '';
        if (!empty($name)) {
            $host = getStatelessHostByKey($name);
            $this->ip = $host->ip;
            $this->port = $host->port;
        } else {
            $this->ip = $config['ip'] ?? '';
            $this->port = $config['port'] ?? '';
        }
        $this->appId = $config['app_id'] ?? '';
        $this->appKey = $config['app_key'] ?? '';
        $this->httpClient = new Client([
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return array
     */
    protected function sendRequest($uri, array $params)
    {
        $data = [
            'appid' => $this->appId,
            'sign' => $this->buildSign($params),
            'timestamp' => time(),
            'data' => $params,
        ];
        $response = $this->httpClient->post($this->buildUrl($uri), ['json' => $data]);
        $contents = $response->getBody()->getContents();
        Log::info(json_encode([
            'Request Url' => $this->buildUrl($uri),
            'Request Params' => $params,
            'Response Status' => $response->getStatusCode(),
            'Response Headers' => $response->getHeaders(),
            'Response Body' => $contents,
        ]));
        $jsonData = json_decode($contents, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new WorkbenchError("招商工作台返回非JSON格式数据: {$contents}");
        }
        if ($jsonData['code'] != 0) {
            throw new WorkbenchError("招商工作台返回Code:{$jsonData['code']}({$jsonData['msg']})", $jsonData['code']);
        }
        return $jsonData['data'];
    }

    /**
     * @param $uri
     * @return string
     */
    protected function buildUrl($uri)
    {
        return "http://{$this->ip}:{$this->port}/" . ltrim($uri, '/');
    }

    /**
     * @param array $params
     * @return string
     */
    protected function buildSign(array $params)
    {
        return md5($this->appId . $this->appKey . json_encode($params));
    }
}

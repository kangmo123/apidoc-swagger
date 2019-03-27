<?php

namespace App\Services\OpenAPI;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;

class OpenAPIClient
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $appKey;

    /**
     * @var string
     */
    protected $appSecret;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * OpenAPIService constructor.
     */
    public function __construct()
    {
        $config = config('services.open-api');
        $this->host = $config['host'];
        $this->appKey = $config['app_key'];
        $this->appSecret = $config['app_secret'];
        $this->httpClient = new Client([
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return array
     */
    protected function sendRequest($method, $uri, array $params)
    {
        $method = strtoupper($method);
        $url = $this->buildUrl($uri);
        $paramType = ($method == 'GET') ? 'query' : 'json';
        $params = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
        ] + $params;
        try {
            $response = $this->httpClient->request($method, $url, [$paramType => $params]);
        } catch (GuzzleException $e) {
            throw new OpenAPIError("OpenAPI请求出错:".$e->getMessage(), 0, $e);
        }
        $contents = $response->getBody()->getContents();
        Log::info(json_encode([
            'Request Url' => $url,
            'Request Params' => $params,
            'Response Status' => $response->getStatusCode(),
            'Response Headers' => $response->getHeaders(),
            'Response Body' => $contents,
        ]));
        $jsonData = json_decode($contents, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new OpenAPIError("OpenAPI返回非JSON格式数据: {$contents}");
        }
        if ($jsonData['code'] != 0) {
            throw new OpenAPIError("OpenAPI返回Code:{$jsonData['code']}({$jsonData['msg']})", $jsonData['code']);
        }
        return $jsonData['data'];
    }

    /**
     * @param string $uri
     * @return string
     */
    protected function buildUrl($uri)
    {
        return rtrim($this->host, '/') . '/' . ltrim($uri, '/');
    }
}

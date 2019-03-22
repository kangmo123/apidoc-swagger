<?php

namespace App\Library\Http;

use App\Exceptions\API\BadRequest;
use App\Exceptions\API\Conflict;
use App\Exceptions\API\Forbidden;
use App\Exceptions\API\InternalServerError;
use App\Exceptions\API\MethodNotAllowed;
use App\Exceptions\API\NotFound;
use App\Exceptions\API\ServiceUnavailable;
use App\Exceptions\API\TooManyRequests;
use App\Exceptions\API\Unauthorized;
use App\Exceptions\API\UnsupportedMediaType;
use App\Exceptions\API\ValidationFailed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class HttpClient
{

    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";

    /**
     * @var ClientInterface
     */
    protected $client = null;

    /**
     * @var array
     */
    protected $headers = [];

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param $url
     * @param $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($url, $params)
    {
        $options = $this->makeGetOptions($params);
        return $this->request(self::GET, $url, $options);
    }

    /**
     * @param $url
     * @param $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($url, $params)
    {
        $options = $this->makePostOptions($params);
        return $this->request(self::POST, $url, $options);
    }

    public function put($url, $params)
    {
        $options = $this->makePostOptions($params);
        return $this->request(self::PUT, $url, $options);
    }

    /**
     * @param $method
     * @param $url
     * @param $options
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method, $url, $options)
    {
        try {
            $resp = $this->client->request($method, $url, $options);
            $ret = (string)$resp->getBody();
            return json_decode($ret, true);
        } catch (RequestException $e) {
            $e = $this->handleGuzzleException($e, $method, $url, $options);
            throw $e;
        }
    }

    protected function makeGetOptions($params)
    {
        return $this->makeOptions($params, RequestOptions::QUERY);
    }

    protected function makePostOptions($params)
    {
        return $this->makeOptions($params, RequestOptions::JSON);
    }

    protected function makeOptions($params, $paramKey)
    {
        $options = [
            $paramKey => $params,
            RequestOptions::HTTP_ERRORS => true,
        ];
        if (!empty($this->headers)) {
            $options[RequestOptions::HEADERS] = $this->headers;
        }
        return $options;
    }

    /**
     * @param RequestException $e
     * @param $method
     * @param $url
     * @param $options
     * @return ServiceUnavailable|TooManyRequests|Unauthorized|UnsupportedMediaType|ValidationFailed|RequestException|\RuntimeException
     */
    protected function handleGuzzleException(RequestException $e, $method, $url, $options)
    {
        $urlInfo = parse_url($url);
        $host = $urlInfo["host"] ?? "";
        $path = $urlInfo["path"] ?? "";
        $fmt = "%s %s%s options: %s. status: %d, body: %s\n";
        if ($e instanceof ClientException) {
            //客户端导致的错误，例如参数错误，info日志
            $resp = $e->getResponse();
            $e = $this->handleClientException($e);
            $log = vsprintf($fmt, [
                $method,
                $host,
                $path,
                json_encode($options, JSON_UNESCAPED_UNICODE),
                $resp->getStatusCode(),
                $resp->getBody()
            ]);
            Log::info($log);
        }
        if ($e instanceof ServerException) {
            //服务端错误，error日志
            $resp = $e->getResponse();
            $e = $this->handleServerException($e);
            $msg = vsprintf($fmt, [
                $method,
                $host,
                $path,
                json_encode($options, JSON_UNESCAPED_UNICODE),
                $resp->getStatusCode(),
                $resp->getBody()
            ]);
            Log::error($msg);
        }
        if ($e instanceof TooManyRedirectsException) {
            $fmt = "%s %s%s options: %s. too many redirects";
            $msg = vsprintf($fmt, [
                $method,
                $host,
                $path,
                json_encode($options, JSON_UNESCAPED_UNICODE),
            ]);
            Log::error($msg);
        }
        if ($e instanceof ConnectException) {
            $fmt = "%s %s%s options: %s. connection error";
            $msg = vsprintf($fmt, [
                $method,
                $host,
                $path,
                json_encode($options, JSON_UNESCAPED_UNICODE),
            ]);
            Log::error($msg);
        }
        return $e;
    }

    protected function handleServerException(ServerException $e)
    {
        $resp = $e->getResponse();
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();
        if (empty($body)) {
            $msg = "";
        } else {
            $body = json_decode($body, true);
            $msg = $body["msg"];
        }
        switch ($status) {
            case InternalServerError::STATUS_CODE:
                return new \RuntimeException($msg);
            case ServiceUnavailable::STATUS_CODE:
                return new ServiceUnavailable($msg);
            default:
                return $e;
        }
    }

    protected function handleClientException(ClientException $e)
    {
        $resp = $e->getResponse();
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();
        if (empty($body)) {
            $msg = "";
        } else {
            $body = json_decode($body, true);
            $msg = $body["msg"];
        }

        switch ($status) {
            case BadRequest::STATUS_CODE:
                return new BadRequest($msg);
            case Conflict::STATUS_CODE:
                return new Conflict($msg);
            case Forbidden::STATUS_CODE:
                return new Forbidden($msg);
            case MethodNotAllowed::STATUS_CODE:
                return new MethodNotAllowed($msg);
            case NotFound::STATUS_CODE:
                return new NotFound($msg);
            case TooManyRequests::STATUS_CODE:
                return new TooManyRequests($msg);
            case Unauthorized::STATUS_CODE:
                return new Unauthorized($msg);
            case UnsupportedMediaType::STATUS_CODE:
                return new UnsupportedMediaType($msg);
            case ValidationFailed::STATUS_CODE:
                return new ValidationFailed($msg);
            default:
                return $e;
        }
    }

    /**
     * Set http request header
     * @param $k
     * @param $v
     */
    public function setHeader($k, $v)
    {
        $this->headers[$k] = $v;
    }

    /**
     * Set http request headers. This method will overwrite headers.
     * @param $headers
     */
    public function setHeaders(
        $headers
    ) {
        $this->headers = $headers;
    }
}

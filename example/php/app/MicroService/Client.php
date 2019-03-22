<?php

namespace App\MicroService;

use App\Library\Http\HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * Class Client
 * @package App\MicroService
 */
abstract class Client
{

    /**
     * @var HttpClient
     */
    protected $client = null;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    abstract protected function getMethods();

    abstract protected function getServiceName();

    protected function callService($api, $arguments)
    {
        $method = isset($api['method']) ? strtoupper($api['method']) : 'GET';
        $uri = $api['uri'];
        $replacement = $api['replacement'] ?? false;
        $url = $this->makeUrl($uri, $arguments, $replacement);
        Log::info("[$method] $url\n" . json_encode($arguments, JSON_UNESCAPED_UNICODE));
        switch ($method) {
            case 'POST':
                $ret = $this->client->post($url, $arguments);
                break;
            case 'GET':
                $ret = $this->client->get($url, $arguments);
                break;
            case 'PUT':
                $ret = $this->client->put($url, $arguments);
                break;
            default:
                throw new \RuntimeException('revenue service api config error');
        }
        return $ret;
    }

    protected function makeUrl($uri, &$arguments, $replacement = false)
    {
        if ($replacement) {
            $uri = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($match) use (&$arguments) {
                $value = $arguments[$match[1]];
                unset($arguments[$match[1]]);
                return $value;
            }, $uri);
        }
        return sprintf("http://%s%s", $this->getServiceName(), $uri);
    }

    public function __call($name, $arguments)
    {
        if (array_key_exists($name, $this->getMethods())) {
            $api = $this->getMethods()[$name];
            $arguments = !empty($arguments) ? $arguments[0] : $arguments;
            return $this->callService($api, $arguments);
        }
        throw new \RuntimeException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

}
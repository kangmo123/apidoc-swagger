<?php

namespace App\Exceptions;

use Throwable;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

class ErrorReporter
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * ErrorReporter constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'connect_timeout' => 3,
            'timeout'         => 5,
        ]);
        try {
            $this->logger = app(LoggerInterface::class);
        } catch (Throwable $exception) {
            $this->logger = null;
        }
        if (!app()->runningInConsole()) {
            $this->request = app('request');
        }
    }

    /**
     * @param Throwable $throwable
     */
    public function report(Throwable $throwable)
    {
        $this->writeToLog($throwable);
        if ($url = $this->getReportUrl()) {
            $this->reportToUrl($url, $throwable);
        }
    }

    /**
     * @param Throwable $throwable
     */
    protected function writeToLog(Throwable $throwable)
    {
        if ($this->logger) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @return null|string
     */
    protected function getReportUrl()
    {
        $reportUrl = config('app.report_url');
        if (empty($reportUrl)) {
            return null;
        }
        $reportUrl = rtrim($reportUrl, '/');
        return $reportUrl . '/msa.error?time=' . microtime(true);
    }

    /**
     * @param $url
     * @param Throwable $throwable
     */
    protected function reportToUrl($url, Throwable $throwable)
    {
        try {
            $this->client->post($url, [
                'json' => $this->getReportData($throwable),
            ]);
        } catch (Throwable $throwable) {
            \Log::error('上报信息到Fluentd出错:' . $throwable->getMessage());
        }
    }

    /**
     * @param Throwable $throwable
     *
     * @return array
     */
    protected function getReportData(Throwable $throwable)
    {
        $data = [
            'app'       => $this->getAppInfo(),
            'user'      => $this->getUserInfo(),
            'exception' => $this->formatException($throwable),
        ];
        if ($this->request) {
            $data['request'] = $this->formatRequest($this->request);
        }
        return $data;
    }

    /**
     * @return array
     */
    protected function getAppInfo()
    {
        return [
            'name'        => config('app.name'),
            'environment' => app()->environment(),
        ];
    }

    /**
     * @return array
     */
    protected function getUserInfo()
    {
        return [
            'staff_name' => $this->request ? $this->request->header('X-Staff-Name') : '',
        ];
    }

    /**
     * @param Throwable $throwable
     *
     * @return array
     */
    protected function formatException(Throwable $throwable)
    {
        $result = [
            'code'    => $throwable->getCode(),
            'file'    => $throwable->getFile(),
            'line'    => $throwable->getLine(),
            'message' => $throwable->getMessage(),
            'trace'   => $throwable->getTraceAsString(),
        ];
        return $result;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function formatRequest(Request $request)
    {
        return [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'headers' => array_map(function ($header) {
                return isset($header[0]) ? $header[0] : '';
            }, $request->headers->all()),
            'params' => $request->input(),
        ];
    }
}

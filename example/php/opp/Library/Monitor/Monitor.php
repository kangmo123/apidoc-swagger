<?php

namespace App\Library\Monitor;

use Illuminate\Http\Request;

class Monitor
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * Monitor constructor.
     */
    public function __construct()
    {
        if (!app()->runningInConsole()) {
            $this->request = app('request');
        }
    }

    /**
     * @param string $key
     * @param int $val
     * @param array $ext
     * @return bool
     */
    public function report($key, $val = 1, array $ext = [])
    {
        if ($url = $this->getReportUrl()) {
            $fp = stream_socket_client($url, $errno, $errstr);
            if (!$fp) {
                \Log::error("上报信息到Fluentd出错，ERROR: {$errno} - {$errstr}");
                return false;
            } else {
                $message = json_encode($this->getReportData($key, $val, $ext));
                fwrite($fp, $message . "\n");
                fclose($fp);
            }
        }
        return true;
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
        $urlInfo = parse_url($reportUrl);
        return 'udp://' . $urlInfo['host'] . ':' . $urlInfo['port'];
    }

    /**
     * @param string $key
     * @param int $val
     * @param array $ext
     * @return array
     */
    protected function getReportData($key, $val = 1, array $ext = [])
    {
        $data = [
            'app' => config('app.name'),
            'env' => app()->environment(),
            'key' => (string)$key,
            'val' => (int)$val,
            'ext' => (array)$ext,
        ];
        if ($this->request) {
            $data['request_id'] = $this->request->header('x-request-id');
        }
        return $data;
    }
}

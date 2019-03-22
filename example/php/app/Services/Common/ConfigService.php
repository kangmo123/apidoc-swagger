<?php

namespace App\Services\Common;


use App\Constant\TaskConstant;
use App\MicroService\TaskClient;
use Carbon\Carbon;

class ConfigService
{

    /**
     * @var TaskClient
     */
    protected $client = null;

    public function __construct(TaskClient $client)
    {
        $this->client = $client;
    }

    public function getConfig($configKeys)
    {
        if (is_array($configKeys)) {
            $configKeys = implode(',', $configKeys);
        }
        $ret = $this->client->getConfigPair(['key' => $configKeys]);
        return $ret['data'];
    }

    /**
     * 获取运营管理的总监组的team数组
     * @param $groupId
     * @return array
     */
    public function getOperatorTaskTargets($groupId)
    {
        $key = TaskConstant::getOperatorTaskTargetsKey($groupId);
        $data = $this->getConfig($key);
        return $data[$key] ?? [];
    }

    /**
     * 获取下个Q开始的日期
     * @return Carbon
     */
    public function getTaskBeginDate()
    {
        $key = TaskConstant::CONFIG_DIFF_DAYS_NEXT_QUARTER_OF_TASK;
        $data = $this->getConfig($key);
        $day = $data[$key] ?? TaskConstant::DEFAULT_DIFF_DAYS;
        $date = Carbon::create()->lastOfQuarter()->subDays($day)->startOfDay();
        return $date;
    }

}
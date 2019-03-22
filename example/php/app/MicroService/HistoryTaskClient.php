<?php

namespace App\MicroService;

/**
 * Class TaskClient
 * @package App\MicroService\Revenue
 * @author caseycheng <caseycheng@tencent.com>
 *
 * @method array getConfigPair(array $configKeys)
 * @method array getTask(array $params)
 * @method array getTotalTask(array $params)
 * @method array createTotalTask(array $params)
 * @method array updateTotalTask(array $params)
 * @method array getSubTask(array $params)
 * @method array getMineTask(array $params)
 * @method array createSubTask(array $params)
 * @method array updateSubTask(array $params)
 * @method array getTaskTree(array $params)
 * @method array getAllSubTask(array $params)
 * @method array lock(array $params)
 * @method array unlock(array $params)
 * @method array upload(array $params)
 * @method array getForecast(array $params)
 */
class HistoryTaskClient extends RevenueClient
{
    /**
     * @var array
     */
    protected $methods = [
        "getSalesTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/sales",
        ],
        "getTeamsTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/teams",
        ],
        "getCentresTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/centres",
        ],
        "getAreasTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/areas",
        ],
        "getDepartmentsTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/departments",
        ],
        "getNationsTask" => [
            "method" => "get",
            "uri" => "/v1/task/history/nations",
        ],
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "revenue.service";
    }

    public function getSaleTask($saleId, $period, $channelType = 'direct')
    {
        $params = [
            'sale_id' => $saleId,
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getSalesTask($params);
        return $taskInfo['data'] ?? [];
    }

    public function getTeamTask($teamId, $period, $channelType = 'direct')
    {
        $params = [
            'team_id' => $teamId,
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getTeamsTask($params);
        return $taskInfo['data'] ?? [];
    }

    public function getCentreTask($teamId, $period, $channelType = 'direct')
    {
        $params = [
            'centre_id' => $teamId,
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getCentresTask($params);
        return $taskInfo['data'] ?? [];
    }

    public function getAreaTask($teamId, $period, $channelType = 'direct')
    {
        $params = [
            'area_id' => $teamId,
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getAreasTask($params);
        return $taskInfo['data'] ?? [];
    }

    public function getDepartmentTask($teamId, $period, $channelType = 'direct')
    {
        $params = [
            'department_id' => $teamId,
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getDepartmentsTask($params);
        return $taskInfo['data'] ?? [];
    }

    public function getNationTask($period, $channelType = 'direct')
    {
        $params = [
            'period' => $period,
            'channel_type' => $channelType,
        ];

        $taskInfo = $this->getNationsTask($params);
        return $taskInfo['data'] ?? [];
    }
}

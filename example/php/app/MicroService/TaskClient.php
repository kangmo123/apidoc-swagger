<?php

namespace App\MicroService;

use App\Constant\TaskConstant;

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
 * @method array queryTask(array $params)
 */
class TaskClient extends RevenueClient
{
    protected $methods = [
        "getConfigPair" => [
            "method" => "get",              //默认是get
            "uri" => "/v1/configs/pair",
        ],
        "getTask" => [
            "method" => "get",
            "uri" => "/v1/task/{task_id}",
            "replacement" => true,          //是否需要替换占位符
        ],
        "queryTask" => [
            "method" => "post",
            "uri" => "/v1/task/query",
        ],
        "getTotalTask" => [
            "method" => "get",
            "uri" => "/v1/task/total",
        ],
        "updateTotalTask" => [
            "method" => "put",
            "uri" => "/v1/task/total",
        ],
        "getMineTask" => [
            "method" => "get",
            "uri" => "/v1/task/mine"
        ],
        "getSubTask" => [
            "method" => "get",
            "uri" => "/v1/task/sub"
        ],
        "updateSubTask" => [
            "method" => "put",
            "uri" => "/v1/task/sub"
        ],
        "getTaskTree" => [
            "method" => "get",
            "uri" => "/v1/task/tree"
        ],
        "getForecast" => [
            "method" => "post",
            "uri" => "/v1/forecasts"
        ],
        "getAllSubTask" => [
            "method" => "get",
            "uri" => "/v1/task/sub/all"
        ],
        "lock" => [
            "method" => "post",
            "uri" => "/v1/task/lock"
        ],
        "unlock" => [
            "method" => "post",
            "uri" => "/v1/task/unlock"
        ],
        "upload" => [
            "method" => "post",
            "uri" => "/v1/task/upload"
        ],
        "getSalesTask" => [
            "method" => "get",
            "uri" => "/v1/task/sales",
        ],
        "getTeamsTask" => [
            "method" => "get",
            "uri" => "/v1/task/teams",
        ],
        "getCentresTask" => [
            "method" => "get",
            "uri" => "/v1/task/centres",
        ],
        "getAreasTask" => [
            "method" => "get",
            "uri" => "/v1/task/areas",
        ],
        "getDepartmentsTask" => [
            "method" => "get",
            "uri" => "/v1/task/departments",
        ],
        "getNationsTask" => [
            "method" => "get",
            "uri" => "/v1/task/nations",
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

    /**
     * 获取任务
     *
     * @param $targetId
     * @param $period
     * @param null $perPage
     * @param null $status
     * @param null $type
     * @return array
     */
    public function getTeamSaleTask($targetId, $period, $perPage = null, $status = null, $type = null)
    {
        $params = [
            'target_id' => $targetId,
            'period' => $period,
        ];
        if (!empty($type)) {
            $params['type'] = $type;
        }
        if (!empty($status)) {
            $params['status'] = $status;
        }
        if (!empty($perPage)) {
            $params['per_page'] = $perPage;
        }
        $taskInfo = $this->queryTask($params);
        $data = [];
        if (empty($taskInfo) || !isset($taskInfo['data'])) {
            return $data;
        }
        foreach ($taskInfo['data'] as $key => $value) {
            $data[$value['target_id']] = $value;
        }
        return $data;
    }

    /**
     * 获取总监预估
     *
     * @param $teamId
     * @param $year
     * @param $quarter
     * @return array|mixed
     */
    public function getTeamForecast($teamId, $year, $quarter)
    {
        if (empty($teamId)) {
            return [];
        }
        if (is_array($teamId)) {
            $teamId = implode(',', $teamId);
        }
        $params = [
            'team_id' => $teamId,
            'year' => $year,
            'quarter' => $quarter,
        ];
        $forecastInfo = $this->getForecast($params);
        $data = $forecastInfo['data'];
        if (empty($data)) {
            return $data;
        }
        //聚合数据
        $ret = [];
        foreach ($data as $forecast) {
            $product = (int)$forecast['product'];
            $productKey = TaskConstant::$productDict[$product];
            $teamId = $forecast['team_id'];
            if (!array_key_exists($teamId, $ret)) {
                $ret[$teamId] = [];
            }
            if (!array_key_exists($productKey, $ret[$teamId])) {
                $ret[$teamId][$productKey] = (int)$forecast['forecast'];
            }
            $ret[$teamId][$productKey] += (int)$forecast['forecast'];
        }
        return $ret;
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

<?php

namespace App\MicroService;

use App\Constant\ProjectConst;
use Carbon\Carbon;

/**
 * Class RevenueClient
 * @package App\MicroService
 */
class ForecastClient extends Client
{
    const REVENUE_DEFAULT_SIZE = 0;
    const REVENUE_DEFAULT_PAGE = 1;
    protected $methods = [
        'teamsForecast' => [
            'type' => 'get',
            'uri' => '/v1/forecasts/teams'
        ],
        'centresForecast' => [
            'type' => 'get',
            'uri' => '/v1/forecasts/centres'
        ],
        'areasForecast' => [
            'type' => 'get',
            'uri' => '/v1/forecasts/areas'
        ],
        'departmentsForecast' => [
            'type' => 'get',
            'uri' => '/v1/forecasts/departments'
        ],
        'nationsForecast' => [
            'type' => 'get',
            'uri' => '/v1/forecasts/nations'
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
     * 获取小组层级的业绩数
     *
     * @param $year
     * @param $quarter
     * @param $teamId
     * @param string $channelType
     * @return array
     */
    public function getTeamForecast(
        $year,
        $quarter,
        $teamId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }

        if (!empty($centreId)) {
            $params['centre_id'] = $centreId;
        }

        $jsonData = $this->teamsForecast($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取总监层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $centreId
     * @param string $channelType
     * @return array
     */
    public function getCentreForecast(
        $year,
        $quarter,
        $centreId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        if (!empty($centreId)) {
            $params['centre_id'] = $centreId;
        }

        if (!empty($areaId)) {
            $params['area_id'] = $areaId;
        }

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->centresForecast($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取片区层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $areaId
     * @param string $channelType
     * @return array
     */
    public function getAreaForecast(
        $year,
        $quarter,
        $areaId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        if (!empty($areaId)) {
            $params['area_id'] = $areaId;
        }

        $jsonData = $this->areasForecast($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取部门层级业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $departmentId
     * @param string $channelType
     * @return array
     */
    public function getDepartmentForecast(
        $year,
        $quarter,
        $departmentId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        if (!empty($departmentId)) {
            $params['department_id'] = $departmentId;
        }

        $jsonData = $this->departmentsForecast($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取全国层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @return array
     */
    public function getCountryForecast(
        $year,
        $quarter,

        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        $jsonData = $this->nationsForecast($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }
}

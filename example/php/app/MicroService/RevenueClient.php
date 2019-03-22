<?php

namespace App\MicroService;

use App\Constant\ProjectConst;
use Carbon\Carbon;

/**
 * Class RevenueClient
 * @package App\MicroService
 *
 * @method array salesRevenueQuarterly(array $params)
 * @method array teamsRevenueQuarterly(array $params)
 * @method array centresRevenueQuarterly(array $params)
 * @method array areasRevenueQuarterly(array $params)
 * @method array departmentsRevenueQuarterly(array $params)
 */
class RevenueClient extends Client
{
    const REVENUE_DEFAULT_SIZE = 0;
    const REVENUE_DEFAULT_PAGE = 1;

    protected $methods = [
        'clientsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/clients'
        ],
        'salesRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/sales'
        ],
        'teamsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/teams'
        ],
        'centresRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/centres'
        ],
        'areasRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/areas'
        ],
        'departmentsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/departments'
        ],
        'searchClientRelationPairRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/clients/relations'
        ],
        'nationsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/nations'
        ],
        'updateTime' => [
            'type' => 'get',
            'uri' => '/v1/status/uptime'
        ],
        'timeRange' => [
            'type' => 'get',
            'uri' => '/v1/status/range'
        ],
        //排期、效果消耗级别的业绩
        'schedulesRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/schedules/quarterly'
        ],
        'accountsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/accounts/quarterly'
        ],
        'agentsRevenueQuarterly' => [
            'type' => 'get',
            'uri' => '/v1/revenue/clients'
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
     * 获取更新业绩时间
     *
     * @param null $year
     * @param null $quarter
     * @param string $outFormat
     * @param string $channelType
     * @return string
     */
    public function getRevenueUpdateTime(
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $outFormat = 'Y-m-d'
    ) {
        $sourceFormat = 'Y-m-d H:i:s';
        $params = [
            'channel_type' => $channelType,
            'year' => $year,
            'quarter' => $quarter,
        ];

        $jsonData = $this->updateTime($params);
        /**
         * 有数据就取数据，拿到carbon对象
         */
        if (!empty($jsonData["update_time"])) {
            $updateTime = $jsonData["update_time"];
            $time = Carbon::createFromFormat($sourceFormat, Carbon::parse($updateTime));
        } else {
            $time = Carbon::now();
        }
        return $time->format($outFormat);
    }

    /**
     * 获取业绩选择时间范围
     *
     * @param string $channelType
     * @return array
     */
    public function getRevenuePeriod($channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT)
    {
        $jsonData = $this->timeRange([
            'channel_type' => $channelType,
        ]);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取销售层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $saleId
     * @param $teamId
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getSaleRevenueQuarterly(
        $year,
        $quarter,
        $saleId,
        $teamId,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];
        /**
         * 获取销售本层的数据传saleId
         */
        if (!empty($saleId)) {
            $params['sale_id'] = $saleId;
        }
        /**
         * 组长获取底下所有销售的数据传teamId
         */
        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }
        /**
         * 资源类型不为空，带上资源类型筛选
         */
        if (!empty($product)) {
            $params['product'] = $product;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->salesRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取小组层级的业绩数
     *
     * @param $year
     * @param $quarter
     * @param $teamId
     * @param $centreId
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getTeamRevenueQuarterly(
        $year,
        $quarter,
        $teamId,
        $centreId,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->teamsRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取总监层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $centreId
     * @param $areaId
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getCentreRevenueQuarterly(
        $year,
        $quarter,
        $centreId,
        $areaId,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
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

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->centresRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取片区层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $areaId
     * @param $departmentId
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getAreaRevenueQuarterly(
        $year,
        $quarter,
        $areaId,
        $departmentId,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
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
        if (!empty($departmentId)) {
            $params['department_id'] = $departmentId;
        }
        if (!empty($product)) {
            $params['product'] = $product;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->areasRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取部门层级业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $departmentId
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getDepartmentRevenueQuarterly(
        $year,
        $quarter,
        $departmentId,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
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
        if (!empty($product)) {
            $params['product'] = $product;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->departmentsRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取全国层级的业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $product
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getCountryRevenueQuarterly(
        $year,
        $quarter,
        $product,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
        ];

        if (!empty($product)) {
            $params['product'] = $product;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        /**
         * 条数
         */
        if ($limit >= 0) {
            $params['per_page'] = $limit;
        }

        $jsonData = $this->nationsRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取客户层级的业绩数据
     *
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $product
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getClientsRevenueQuarterly(
        $clientId,
        $saleId,
        $teamId,
        $product,
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
            'per_page' => 0,
        ];
        if (!empty($clientId)) {
            $params['client_id'] = $clientId;
        }

        if (!empty($saleId)) {
            $params['sale_id'] = $saleId;
        }

        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }

        if (!empty($product)) {
            $params['product'] = $product;
        }

        if (!empty($channelType)) {
            $params['channel_type'] = $channelType;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        $jsonData = $this->clientsRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * @param $year
     * @param $quarter
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $centreId
     * @param $areaId
     * @param string $channelType
     * @return array
     */
    public function searchClientSaleRelationQuarterly(
        $year,
        $quarter,
        $clientId,
        $saleId,
        $teamId,
        $centreId,
        $areaId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
            'per_page' => 50, //
        ];
        if (!empty($clientId)) {
            $params['client_id'] = $clientId;
        }

        if (!empty($saleId)) {
            $params['sale_id'] = $saleId;
        }

        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }

        if (!empty($centreId)) {
            $params['centre_id'] = $centreId;
        }

        if (!empty($areaId)) {
            $params['area_id'] = $areaId;
        }
        $jsonData = $this->searchClientRelationPairRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * @param $agentId
     * @param $saleId
     * @param $teamId
     * @param $product
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getAgentsRevenueQuarterly(
        $agentId,
        $saleId,
        $teamId,
        $product,
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        //查询客户业绩微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'channel_type' => $channelType,
            'per_page' => 0,
        ];
        if (!empty($agentId)) {
            $params['agent_id'] = $agentId;
        }

        if (!empty($saleId)) {
            $params['sale_id'] = $saleId;
        }

        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }

        if (!empty($product)) {
            $params['product'] = $product;
        }

        if (!empty($channelType)) {
            $params['channel_type'] = $channelType;
        }

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

        $jsonData = $this->agentsRevenueQuarterly($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

}

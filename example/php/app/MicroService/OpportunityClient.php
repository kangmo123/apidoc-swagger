<?php

namespace App\MicroService;


/**
 * Class OpportunityClient
 * @package App\MicroService
 *
 * @method array projectsOpportunity($params)
 * @method array searchOpportunity($params)
 */
class OpportunityClient extends Client
{
    const DEFAULT_LIMIT = 0;
    const DEFAULT_PAGE = 1;

    protected $methods = [
        /**
         * 商机、opp数据只有按季度维度的
         */
        'clientsOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/clients'
        ],
        'salesOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/sales'
        ],
        'teamsOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/teams'
        ],
        'centresOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/centres'
        ],
        'areasOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/areas'
        ],
        'departmentsOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/departments'
        ],
        'nationsOpportunity' => [
            'type' => 'get',
            'uri' => '/v1/forecast/nation'
        ],
        "projectsOpportunity" => [
            "method" => "get",
            "uri" => "/v1/forecast/projects",
        ],
        "searchOpportunity" => [
            "method" => "get",
            "uri" => "/v1/opportunities/search",
        ],
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "opp.service";
    }

    /**
     * 获取销售层级的商机、opp数据
     *
     * @param $year
     * @param $quarter
     * @param $saleId
     * @param $teamId
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getSaleOpportunity(
        $year,
        $quarter,
        $saleId,
        $teamId,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询销售商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
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

        /**
         * 资源类型不为空，带上资源类型筛选
         */
        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->salesOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取小组层级的商机、opp数据
     *
     * @param $year
     * @param $quarter
     * @param $teamId
     * @param $centreId
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getTeamOpportunity(
        $year,
        $quarter,
        $teamId,
        $centreId,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询小组层级商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
        ];
        if (!empty($teamId)) {
            $params['team_id'] = $teamId;
        }

        if (!empty($centreId)) {
            $params['centre_id'] = $centreId;
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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->teamsOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取总监层级的商机、opp数据
     *
     * @param $year
     * @param $quarter
     * @param $centreId
     * @param $areaId
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getCentreOpportunity(
        $year,
        $quarter,
        $centreId,
        $areaId,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询总监层级商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
        ];
        if (!empty($centreId)) {
            $params['centre_id'] = $centreId;
        }

        if (!empty($areaId)) {
            $params['area_id'] = $areaId;
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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->centresOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取片区层级的商机、opp数据
     *
     * @param $year
     * @param $quarter
     * @param $areaId
     * @param $departmentId
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getAreaOpportunity(
        $year,
        $quarter,
        $areaId,
        $departmentId,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询片区层级商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
        ];

        if (!empty($areaId)) {
            $params['area_id'] = $areaId;
        }

        if (!empty($departmentId)) {
            $params['department_id'] = $departmentId;
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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->areasOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * @param $year
     * @param $quarter
     * @param $departmentId
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     */
    public function getDepartmentOpportunity(
        $year,
        $quarter,
        $departmentId,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询片区层级商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
        ];

        if (!empty($departmentId)) {
            $params['department_id'] = $departmentId;
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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->departmentsOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取全国层级的商机、opp数据
     *
     * @param $year
     * @param $quarter
     * @param $product
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getCountryOpportunity(
        $year,
        $quarter,
        $product,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {
        //查询全国层级商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter
        ];

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

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->nationsOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }

    /**
     * 获取客户层级的商机、opp数据
     *
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $product
     * @param $year
     * @param $quarter
     * @param int $pageNum
     * @param int $limit
     * @return mixed
     * @throws \common\components\exception\MicroServiceException
     */
    public function getClientsOpportunity(
        $clientId,
        $saleId,
        $teamId,
        $product,
        $year,
        $quarter,
        $pageNum = self::DEFAULT_PAGE,
        $limit = self::DEFAULT_LIMIT
    ) {

        //查询客户商机、opp微服务的相关参数
        $params = [
            'year' => $year,
            'quarter' => $quarter,
            'per_page' => 0, //perpage=0 reprents returning all records
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

        /**
         * 页码
         */
        if ($pageNum >= 0) {
            $params['page'] = $pageNum;
        }

//        /**
//         * 条数
//         */
//        if ($limit >= 0) {
//            $params['per_page'] = $limit;
//        }

        if (!empty($product)) {
            $params['product'] = $product;
        }

        $jsonData = $this->clientsOpportunity($params);
        return isset($jsonData["data"]) ? $jsonData["data"] : [];
    }
}

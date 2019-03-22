<?php

/**
 *
 */

namespace App\Services\Revenue\Summary;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\MicroService\OpportunityClient;
use App\MicroService\RevenueClient;
use App\Utils\TimerUtil;
use App\Utils\TreeUtil;
use Carbon\Carbon;

/**
 * Class RevenueSummaryService
 * @package App\Services\Task\Summary
 */
class BaseSummaryService
{
    protected $productTypeTree = null;
    protected $commonField = null;
    protected $oppCommonField = null;
    protected $productTypeColumn = 'product';
    protected $dataGroupByColumnMap = null;
    protected $allBaseProductRevenueData = [];
    protected $allBaseProductOppData = [];
    const REVENUE_DEFAULT_SIZE = 0;
    const REVENUE_DEFAULT_PAGE = 1;
    private $allForecastData = [];

    public function __construct()
    {
        $this->productTypeTree = RevenueConst::$productTypeMergeTree;
        $this->commonField = RevenueConst::$revenueApiCommonField;
        $this->oppCommonField = ['opp_q_forecast', 'opp_q_wip', 'opp_q_ongoing', 'opp_q_order', 'opp_q_remain'];
        $this->dataGroupByColumnMap = [
            ArchitectConstant::ARCHITECT_ACCOUNT => [
                'team_id',
                'sale_id',
                'client_id'
            ],
            ArchitectConstant::ARCHITECT_SALE => [
                'sale_id',
                'team_id'
            ],
            ArchitectConstant::ARCHITECT_LEADER => [
                'team_id',
                'centre_id'
            ],
            ArchitectConstant::ARCHITECT_DIRECTOR => [
                'centre_id',
                'area_id'
            ],
            ArchitectConstant::ARCHITECT_AREA => [
                'area_id',
                'department_id'
            ],
            ArchitectConstant::ARCHITECT_DEPT => [
                'department_id'
            ],
            ArchitectConstant::ARCHITECT_SYSTEM => [
            ]
        ];
    }

    /**
     * 设置业绩数据字段
     * @author: meyeryan@tencent.com
     *
     * @param array $commonFiled
     */
    public function setCommonField(array $commonFiled)
    {
        if (!empty($commonFiled)) {
            $this->commonField = $commonFiled;
        }
    }

    /**
     * 获取业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $id
     * @param $pid
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getRevenueDataQuarterly(
        $year,
        $quarter,
        $archType,
        $id,
        $pid,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        /**
         * 组织架构类型可能为零，不能进行非空判断
         */
        if (empty($year) || empty($quarter) || empty($originProductTypeArr)) {
            return [];
        }

        $typeMap = [];
        foreach ($originProductTypeArr as $type) {
            $typeList = $this->getBaseProductTypeList($type);
            $typeMap[$type] = $typeList;
        }
        $groupColumnList = $this->dataGroupByColumnMap[$archType];
        /**
         * 拿到所有叶子节点的业绩
         */
        $this->fillSaleAllBaseProductRevenueData($year, $quarter, $archType, $id, $pid, $channelType, $pageNum, $limit,
            $groupColumnList);

        if (empty($this->allBaseProductRevenueData)) {
            return [];
        }

        $finalRevenueData = [];
        foreach ($this->allBaseProductRevenueData as $uniqueKey => $value) {
            $finalRevenueData[$uniqueKey] = [];
            foreach ($typeMap as $type => $map) {
                if (empty($map)) {
                    $finalRevenueData[$uniqueKey][$type] = [];
                } else {
                    $finalRevenueData[$uniqueKey][$type] = $this->getSummaryRevenueData($value, $map, $groupColumnList);
                }
            }
        }

        return $finalRevenueData;
    }

    /**
     * 获取销售商机数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $id
     * @param $pid
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getSaleOpportunityDataQuarterly(
        $year,
        $quarter,
        $archType,
        $id,
        $pid,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        /**
         * 组织架构类型可能为零，不能进行非空判断
         */
        if (empty($year) || empty($quarter) || empty($originProductTypeArr)) {
            return [];
        }

        $groupColumnList = $this->dataGroupByColumnMap[$archType];
        $this->fillSaleAllBaseProductOpportunityData($year, $quarter, $archType, $id, $pid, $channelType, $pageNum,
            $limit,
            $groupColumnList
        );
        $finalOppData = [];
        foreach ($this->allBaseProductOppData as $uniqueKey => $oppData) {
            $finalOppData[$uniqueKey] = [];
            foreach ($originProductTypeArr as $type) {
                $finalOppData[$uniqueKey][$type] = $this->getSummaryOpportunityData($type, $oppData, $groupColumnList);
            }
        }
        return $finalOppData;
    }


    /**
     * 给出一个产品类型，获取所有的基础产品类型=》例如：
     * 给出品效整体产品类型，获取所有品牌+效果的产品类型
     * @author: meyeryan@tencent.com
     *
     * @param $productType
     * @return array
     */
    public function getBaseProductTypeList($productType)
    {
        $allLeafNodes = $this->getAllProductTypeLeafNodes();

        if (\in_array($productType, $allLeafNodes)) {
            return [$productType];
        }

        return TreeUtil::getLeafNodesByIndexRecursively($this->productTypeTree, $productType);
    }


    /**
     * 拿到最底层的基础产品类型信息
     * @author: meyeryan@tencent.com
     *
     * @return array
     */
    public function getAllProductTypeLeafNodes()
    {
        return TreeUtil::getAllLeafNodes($this->productTypeTree);
    }

    /**
     * 传入基础数据，把各个字段填充进去，同时按照产品类型聚合好
     * @author: meyeryan@tencent.com
     *
     * @param array $originRevenueList
     * @param array $groupColumnList
     * @param bool $oppFlag
     * @return array
     */
    protected function aggregateDataByProductType(array $originRevenueList, array $groupColumnList, $oppFlag = false)
    {
        if (empty($originRevenueList)) {
            return $originRevenueList;
        }

        $revenueData = [];

        if (empty($oppFlag)) {
            $commonField = $this->commonField;
        } else {
            $commonField = $this->oppCommonField;
        }

        foreach ($originRevenueList as $originData) {
            $uniqueKey = $this->getUniqueKey($originData, $groupColumnList);
            if (!isset($revenueData[$uniqueKey])) {
                $revenueData[$uniqueKey] = [
                    'data' => [],
                    'group_by' => []
                ];
            }
            if (!isset($revenueData[$uniqueKey]['data'][$originData[$this->productTypeColumn]])) {
                $revenueData[$uniqueKey]['data'][$originData[$this->productTypeColumn]] = [];
            }
            /**
             * 把需要相加的数据加上
             */
            $this->fillColumn($revenueData[$uniqueKey]['data'][$originData[$this->productTypeColumn]],
                $originData, $commonField);
            $this->fillGroupByData($revenueData[$uniqueKey]['data'][$originData[$this->productTypeColumn]],
                $originData, $groupColumnList);
            $this->fillGroupByData($revenueData[$uniqueKey]['group_by'],
                $originData, $groupColumnList);
        }

        return $revenueData;
    }

    /**
     * 获取汇总数据
     * @author: meyeryan@tencent.com
     *
     * @param array $originRevenueData
     * @param array $productTypeList
     * @param array $groupColumnList
     * @return array
     */
    protected function getSummaryRevenueData(array $originRevenueData, array $productTypeList, array $groupColumnList)
    {
        $summaryData = [];

        if (empty($productTypeList)) {
            return $summaryData;
        }

        foreach ($productTypeList as $productType) {
            $data = isset($originRevenueData['data'][$productType]) ? $originRevenueData['data'][$productType] : [];
            /**
             * 填充字段
             */
            $this->fillColumn($summaryData,
                $data);
            $this->fillGroupByData($summaryData,
                $originRevenueData['group_by'], $groupColumnList);
        }

        return $summaryData;
    }

    /**
     * 填充字段
     * @author: meyeryan@tencent.com
     *
     * @param $originData
     * @param array $sourceData
     * @param array $commonField
     */
    protected function fillColumn(&$originData, array $sourceData, array $commonField = [])
    {
        if (empty($commonField)) {
            $commonField = $this->commonField;
        }
        foreach ($commonField as $field) {
            if (!\array_key_exists($field, $originData)) {
                $originData[$field] = 0;
            }
            $originData[$field] += (isset($sourceData[$field]) ? intval($sourceData[$field]) : 0);
        }
    }

    /**
     * 获取产品类型对应的商机、wip数据
     * @author: meyeryan@tencent.com
     *
     * @param $productType
     * @param array $opportunityData
     * @param array $groupColumnList
     * @return array
     */
    protected function getSummaryOpportunityData($productType, array $opportunityData, array $groupColumnList)
    {
        $oppData = [];
        if (array_key_exists($productType, $opportunityData['data'])) {
            $oppData['q_opp'] = $opportunityData['data'][$productType]['opp_q_forecast'] ?? 0;
            $oppData['q_wip'] = $opportunityData['data'][$productType]['opp_q_wip'] ?? 0;
            $oppData['q_opp_ongoing'] = $opportunityData['data'][$productType]['opp_q_ongoing'] ?? 0;
            $oppData['q_opp_remain'] = $opportunityData['data'][$productType]['opp_q_remain'] ?? 0;
            $oppData['q_opp_order'] = $opportunityData['data'][$productType]['opp_q_order'] ?? 0;
        } else {
            if (in_array($productType,
                [RevenueConst::PRODUCT_TYPE_BRAND_EFFECT, RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER])) {
                $oppData['q_opp'] = $opportunityData['data'][RevenueConst::PRODUCT_TYPE_ALL]['opp_q_forecast'] ?? 0;
                $oppData['q_wip'] = $opportunityData['data'][RevenueConst::PRODUCT_TYPE_ALL]['opp_q_wip'] ?? 0;
                $oppData['q_opp_ongoing'] = $opportunityData['data'][RevenueConst::PRODUCT_TYPE_ALL]['opp_q_ongoing'] ?? 0;
                $oppData['q_opp_remain'] = $opportunityData['data'][RevenueConst::PRODUCT_TYPE_ALL]['opp_q_remain'] ?? 0;
                $oppData['q_opp_order'] = $opportunityData['data'][RevenueConst::PRODUCT_TYPE_ALL]['opp_q_order'] ?? 0;
            } else {
                $oppData['q_opp'] = 0;
                $oppData['q_wip'] = 0;
                $oppData['q_opp_ongoing'] = 0;
                $oppData['q_opp_remain'] = 0;
                $oppData['q_opp_order'] = 0;
            }
        }
        $this->fillGroupByData($oppData, $opportunityData['group_by'], $groupColumnList);
        return $oppData;
    }

    /**
     * 给出一个产品类型，获取包含它本身的所有子节点
     * @author: meyeryan@tencent.com
     *
     * @param $productType
     * @return array
     */
    public function getAllChildrenByProductType($productType)
    {
        $childrenTypes = [$productType];
        $allLeafNodes = $this->getAllProductTypeLeafNodes();

        if (\in_array($productType, $allLeafNodes)) {
            return $childrenTypes;
        }

        $childrenTypes = TreeUtil::getAllChildrenNodesByIndex($this->productTypeTree, $productType);
        return $childrenTypes;
    }


    /**
     * 获取扁平的聚合数据，格式为：唯一键=》[产品类型1=》数据，产品类型2=》数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $id
     * @param $pid
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getFlattenSaleRevenueOpportunityData(
        $year,
        $quarter,
        $archType,
        $id,
        $pid,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        $isHistory = false;
        $date = Carbon::create($year, $quarter * 3, 1);
        if ($date->endOfQuarter() < Carbon::now()) {
            $isHistory = true;
        }
        $oriRevenueDataList = $this->getRevenueDataQuarterly($year,
            $quarter,
            $archType,
            $id,
            $pid,
            $originProductTypeArr, $channelType, $pageNum, $limit);
        TimerUtil::log("获取业绩数据");
        $opportunityDataList = $this->getSaleOpportunityDataQuarterly($year,
            $quarter,
            $archType,
            $id,
            $pid,
            $originProductTypeArr, $channelType, $pageNum, $limit);
        TimerUtil::log("获取商机数据");
        $resultData = $this->getCompleteDataByRevenueAndOpp($oriRevenueDataList, $opportunityDataList, $isHistory);
        TimerUtil::log("组装业绩和商机数据");
        return $resultData;
    }

    /**
     * 预估收入的数据处理
     *
     * @param $revenueData
     * @param $productType
     * @param $uniqueKey
     * @param $baseProductRevenueList
     * @return mixed
     */
    protected function getForecastData($revenueData, $productType, $uniqueKey, $baseProductRevenueList)
    {
        /**
         * 数据存在，直接返回
         */
        if (\array_key_exists($uniqueKey,
                $this->allForecastData) && isset($this->allForecastData[$uniqueKey][$productType]['forecast'])) {
            return $this->allForecastData[$uniqueKey][$productType]['forecast'];
        }

        $this->allForecastData[$uniqueKey] = [
            RevenueConst::PRODUCT_TYPE_EFFECT_ALL => [
                'forecast' => 0
            ],
            RevenueConst::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => [
                'forecast' => 0
            ],
            RevenueConst::PRODUCT_TYPE_ALL => [
                'forecast' => 0
            ],
            RevenueConst::PRODUCT_TYPE_BRAND_EFFECT => [
                'forecast' => 0
            ],
            RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => [
                'forecast' => 0
            ],
        ];

        $effectChildren = $this->getBaseProductTypeList(RevenueConst::PRODUCT_TYPE_EFFECT_ALL);

        /*
         * 效果的预估=效果叶子节点预估之和
         */
        foreach ($effectChildren as $child) {
            $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_EFFECT_ALL]['forecast']
                += ($baseProductRevenueList[$child]['q_forecast'] ?? 0);
        }

        /**
         * 品牌预估=品牌整体下单+wip
         */
        $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_ALL]['forecast']
            = ($revenueData[RevenueConst::PRODUCT_TYPE_ALL]['q_wip'] ?? 0) + ($revenueData[RevenueConst::PRODUCT_TYPE_ALL]['qtd_money'] ?? 0);

        /**
         * 整体=品效整体=品牌预估+效果预估
         */
        $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER]['forecast']
            = $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_BRAND_EFFECT]['forecast']
            = $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_ALL]['forecast'] +
            $this->allForecastData[$uniqueKey][RevenueConst::PRODUCT_TYPE_EFFECT_ALL]['forecast'];

        return $this->allForecastData[$uniqueKey][$productType]['forecast'];
    }


    /**
     * 客户简称层级数据
     *
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getClientsRevenueQuarterly(
        $clientId,
        $saleId,
        $teamId,
        $year,
        $quarter,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        $typeMap = $baseProductTypeList = [];
        foreach ($originProductTypeArr as $type) {
            $typeList = $this->getBaseProductTypeList($type);
            $typeMap[$type] = $typeList;
            if (!empty($typeList)) {
                $baseProductTypeList = array_merge($baseProductTypeList, $typeList);
            }
        }
        $baseProductTypeList = array_unique($baseProductTypeList);
        /**
         * 没有需要查询的数据，则返回空
         */
        if (empty($baseProductTypeList)) {
            return $baseProductTypeList;
        }

        /**
         * 查询所有需要的子产品类型的业绩
         */
        $groupColumnList = $this->dataGroupByColumnMap[ArchitectConstant::ARCHITECT_ACCOUNT];
        $this->fillClientAllBaseProductRevenueData($clientId, $saleId, $teamId, $year, $quarter, $channelType, $pageNum,
            $limit,
            $groupColumnList);
        $finalRevenueData = [];
        foreach ($this->allBaseProductRevenueData as $uniqueKey => $value) {
            $finalRevenueData[$uniqueKey] = [];
            foreach ($typeMap as $type => $map) {
                if (empty($map)) {
                    $finalRevenueData[$uniqueKey][$type] = [];
                } else {
                    $finalRevenueData[$uniqueKey][$type] = $this->getSummaryRevenueData($value, $map, $groupColumnList);
                }
            }
        }
        return $finalRevenueData;
    }

    /**
     * 简称层级商机数据
     *
     * @param $saleId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getClientsOpportunityDataQuarterly(
        $saleId,
        $teamId,
        $year,
        $quarter,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        /**
         * 组织架构类型可能为零，不能进行非空判断
         */
        if (empty($year) || empty($quarter) || empty($originProductTypeArr)) {
            return [];
        }

        $groupColumnList = $this->dataGroupByColumnMap[ArchitectConstant::ARCHITECT_ACCOUNT];
        $this->fillClientAllBaseProductOpportunityData(null, $saleId, $teamId, $year, $quarter, $channelType, $pageNum,
            $limit,
            $groupColumnList);
        $finalOppData = [];
        foreach ($this->allBaseProductOppData as $uniqueKey => $oppData) {
            $finalOppData[$uniqueKey] = [];
            foreach ($originProductTypeArr as $type) {
                $finalOppData[$uniqueKey][$type] = $this->getSummaryOpportunityData($type, $oppData, $groupColumnList);
            }
        }

        return $finalOppData;
    }


    /**
     * 获取唯一键
     * @author: meyeryan@tencent.com
     *
     * @param array $originData
     * @param array $groupByList
     * @return string
     */
    protected function getUniqueKey(array $originData, array $groupByList)
    {
        $uniqueKey = [];
        foreach ($groupByList as $key) {
            $uniqueKey[] = $originData[$key];
        }
        return implode('_', $uniqueKey);
    }

    /**
     * 填充聚合字段数据
     * @author: meyeryan@tencent.com
     *
     * @param $originData
     * @param array $sourceData
     * @param array $groupByColumn
     */
    protected function fillGroupByData(&$originData, array $sourceData, array $groupByColumn = [])
    {
        foreach ($groupByColumn as $field) {
            if (!isset($originData[$field])) {
                $originData[$field] = $sourceData[$field];
            }
        }
    }

    /**
     * 拿汇总数据
     * @author: meyeryan@tencent.com
     *
     * @param array $oriRevenueDataList
     * @param array $opportunityDataList
     * @param $isHistory
     * @return array
     */
    protected function getCompleteDataByRevenueAndOpp(array $oriRevenueDataList, array $opportunityDataList, $isHistory)
    {
        $resultData = [];
        $baseProductTypeForecast = [];

        if (empty($oriRevenueDataList) && empty($opportunityDataList)) {
            return $resultData;
        }

        /**
         * 计算所有叶子节点的预估收入
         */
        if (!empty($this->allBaseProductRevenueData)) {
            foreach ($this->allBaseProductRevenueData as $key => $revenue) {
                $opp = $opportunityDataList[$key] ?? [];
                $baseProductTypeForecast[$key] = [];
                foreach ($revenue['data'] as $product => $value) {
                    $value['product_value'] = $product;
                    $value = array_merge($value, $opp[$product] ?? []);
                    $value['q_forecast'] = $this->getBaseProductForecastData($value, $isHistory);
                    $baseProductTypeForecast[$key][$product] = $value;
                }
            }
        }

        /**
         * 先处理有下单的商机相关数据
         * 合并商机、业绩数据，并计算叶子节点的品牌、效果预估数据，非叶子节点统一取零,预估收入还要从下往上汇总
         */
        if (!empty($oriRevenueDataList)) {
            foreach ($oriRevenueDataList as $uniqueKey => $allData) {
                $opp = $this->allBaseProductOppData[$uniqueKey]['data'] ?? [];

                /**
                 * 预估依赖于wip，所以，必须把下单数据和商机相关数据处理完，再处理预估收入
                 */
                foreach ($allData as $productType => &$data) {
                    $data['product_value'] = $productType;
                    $newOpp = $this->getOppWipData($productType, $opp);
                    $data = array_merge($data, $newOpp);
                }

                /**
                 * 处理预估
                 */
                foreach ($allData as $productType => &$data) {
                    /**
                     * 非叶子节点，则把对应所有叶子节点的收入加和汇总
                     */
                    $subChildren = $this->getBaseProductTypeList($productType);
                    if (\count($subChildren) > 1) {
                        $data['q_forecast'] = $this->getForecastData($allData, $productType, $uniqueKey,
                            $baseProductTypeForecast[$uniqueKey] ?? []);
                    } else {
                        $data['q_forecast'] = $baseProductTypeForecast[$uniqueKey][$productType]['q_forecast'] ?? 0;
                    }
                }

                $resultData[$uniqueKey] = $allData;
            }
        }

        /**
         * 再回来单独处理有商机、无下单的数据，这样就能把有下单无商机、有商机无下单的数据合并--逻辑真恶心
         */
        if (!empty($opportunityDataList)) {
            foreach ($opportunityDataList as $key => $opp) {
                /**
                 * 如果下单数据中已经有数据，就不要处理了
                 */
                if (!empty($oriRevenueDataList) && isset($oriRevenueDataList[$key])) {
                    continue;
                }

                /**
                 * 开始处理有商机、无下单的数据
                 */
                $baseProductTypeForecast[$key] = [];
                foreach ($opp as $product => $value) {
                    $value['product_value'] = $product;
                    $value = array_merge($value, $opp[$product] ?? []);
                    $value['q_forecast'] = $value['q_wip'];
                    $resultData[$key][$product] = $value;
                }
            }
        }

        return empty($resultData) ? [] : $resultData;
    }

    /**
     * 获取销售层级的所有叶子节点业绩数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $id
     * @param $pid
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @param array $groupColumnList
     */
    protected function fillSaleAllBaseProductRevenueData(
        $year,
        $quarter,
        $archType,
        $id,
        $pid,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE,
        array $groupColumnList = []
    ) {
        $allLeafNodes = $this->getAllProductTypeLeafNodes();
        /**
         * 查询所有需要的子产品类型的业绩
         */
        $productTypeListStr = implode(',', $allLeafNodes);
        $revenueData = [];

        /**
         * @var RevenueClient $revenueClient
         */
        $revenueClient = app(RevenueClient::class);
        switch ($archType) {
            /**
             * 销售业绩，需要根据销售id拿，销售以上层级数据需要根据team id拿
             * 如果销售本身属于A组，他下了一个B组的单，这个时候，在业绩概览的地方应该直接根据team id过滤掉，
             * 在层级数据中，需要把其他组的业绩放到异常业绩
             */
            case ArchitectConstant::ARCHITECT_SALE:
                $revenueData = $revenueClient->getSaleRevenueQuarterly($year, $quarter, $id, $pid,
                    $productTypeListStr, $channelType, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
                $revenueData = $revenueClient->getTeamRevenueQuarterly($year, $quarter, $id, $pid,
                    $productTypeListStr, $channelType, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_DIRECTOR:
                $revenueData = $revenueClient->getCentreRevenueQuarterly($year, $quarter, $id, $pid,
                    $productTypeListStr, $channelType, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_AREA:
                $revenueData = $revenueClient->getAreaRevenueQuarterly($year, $quarter, $id, $pid, $productTypeListStr,
                    $channelType,
                    $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_DEPT:
                $revenueData = $revenueClient->getDepartmentRevenueQuarterly($year, $quarter, $id, $productTypeListStr,
                    $channelType,
                    $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_SYSTEM:
                $revenueData = $revenueClient->getCountryRevenueQuarterly($year, $quarter, $productTypeListStr,
                    $channelType,
                    $pageNum,
                    $limit);
                break;
            default:
                break;
        }

        /**
         * 按产品类型=》业绩，拿到叶子节点的业绩数据
         */
        $this->allBaseProductRevenueData = $this->aggregateDataByProductType($revenueData ?? [],
            $groupColumnList);
    }

    /**
     * 获取客户层级的所有叶子节点产品类型业绩数据
     *
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @param array $groupColumnList
     */
    protected function fillClientAllBaseProductRevenueData(
        $clientId,
        $saleId,
        $teamId,
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE,
        array $groupColumnList = []
    ) {
        $allLeafNodes = $this->getAllProductTypeLeafNodes();
        /**
         * 查询所有需要的子产品类型的业绩
         * @var RevenueClient $revenueClient
         */
        $productTypeListStr = implode(',', $allLeafNodes);
        $revenueClient = app(RevenueClient::class);
        $revenueData = $revenueClient->getClientsRevenueQuarterly($clientId, $saleId, $teamId,
            $productTypeListStr, $year, $quarter, $channelType, $pageNum, $limit);
        $this->allBaseProductRevenueData = $this->aggregateDataByProductType($revenueData ?? [],
            $groupColumnList);
    }

    /**
     * 填充销售层级的商机数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $id
     * @param $pid
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @param array $groupColumnList
     */
    protected function fillSaleAllBaseProductOpportunityData(
        $year,
        $quarter,
        $archType,
        $id,
        $pid,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE,
        array $groupColumnList = []
    ) {
        if (ProjectConst::SALE_CHANNEL_TYPE_DIRECT != $channelType) {
            return;
        }
        /**
         * @var OpportunityClient $opportunityClient
         */
        $opportunityClient = app(OpportunityClient::class);
        $opportunityData = [];
        switch ($archType) {
            case ArchitectConstant::ARCHITECT_SALE:
                $opportunityData = $opportunityClient->getSaleOpportunity($year, $quarter, $id, $pid,
                    null, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
                $opportunityData = $opportunityClient->getTeamOpportunity($year, $quarter, $id, $pid,
                    null, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_DIRECTOR:
                $opportunityData = $opportunityClient->getCentreOpportunity($year, $quarter, $id, $pid,
                    null, $pageNum, $limit);
                break;
            case ArchitectConstant::ARCHITECT_AREA:
                $opportunityData = $opportunityClient->getAreaOpportunity($year, $quarter, $id, $pid, null, $pageNum,
                    $limit);
                break;
            case ArchitectConstant::ARCHITECT_DEPT:
                $opportunityData = $opportunityClient->getDepartmentOpportunity($year, $quarter, $id, null, $pageNum,
                    $limit);
                break;
            case ArchitectConstant::ARCHITECT_SYSTEM:
                $opportunityData = $opportunityClient->getCountryOpportunity($year, $quarter, null, $pageNum, $limit);
                break;
            default:
                break;
        }

        $this->allBaseProductOppData = $this->aggregateDataByProductType($opportunityData ?? [], $groupColumnList,
            true);
    }

    /**
     * 填充客户层级的商机数据
     *
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @param array $groupColumnList
     */
    protected function fillClientAllBaseProductOpportunityData(
        $clientId,
        $saleId,
        $teamId,
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE,
        array $groupColumnList = []
    ) {
        if (ProjectConst::SALE_CHANNEL_TYPE_DIRECT != $channelType) {
            return;
        }
        /**
         * 查询所有需要的子产品类型的业绩
         *
         * @var OpportunityClient $opportunityClient
         */
        $opportunityClient = app(OpportunityClient::class);
        $opportunityData = $opportunityClient->getClientsOpportunity($clientId, $saleId, $teamId, null, $year, $quarter,
            $pageNum,
            $limit);
        $this->allBaseProductOppData = $this->aggregateDataByProductType($opportunityData ?? [], $groupColumnList,
            true);
    }

    /**
     * 产品类型、所有商机数据，拿到对应的商机数据
     * @author: meyeryan@tencent.com
     *
     * @param $productType
     * @param array $oppData
     * @return array
     */
    protected function getOppWipData($productType, array $oppData)
    {
        $newOpp = [];
        if (\array_key_exists($productType, $oppData)) {
            $newOpp['q_opp'] = $oppData[$productType]['opp_q_forecast'] ?? 0;
            $newOpp['q_wip'] = $oppData[$productType]['opp_q_wip'] ?? 0;
            $newOpp['q_opp_ongoing'] = $oppData[$productType]['opp_q_ongoing'] ?? 0;
            $newOpp['q_opp_remain'] = $oppData[$productType]['opp_q_remain'] ?? 0;
            $newOpp['q_opp_order'] = $oppData[$productType]['opp_q_order'] ?? 0;
        } else {
            if (!empty($oppData) && in_array($productType,
                    [RevenueConst::PRODUCT_TYPE_BRAND_EFFECT, RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER])) {
                $newOpp['q_opp'] = $oppData[RevenueConst::PRODUCT_TYPE_ALL]['opp_q_forecast'] ?? 0;
                $newOpp['q_wip'] = $oppData[RevenueConst::PRODUCT_TYPE_ALL]['opp_q_wip'] ?? 0;
                $newOpp['q_opp_ongoing'] = $oppData[RevenueConst::PRODUCT_TYPE_ALL]['opp_q_ongoing'] ?? 0;
                $newOpp['q_opp_remain'] = $oppData[RevenueConst::PRODUCT_TYPE_ALL]['opp_q_remain'] ?? 0;
                $newOpp['q_opp_order'] = $oppData[RevenueConst::PRODUCT_TYPE_ALL]['opp_q_order'] ?? 0;
            } else {
                $newOpp['q_opp'] = 0;
                $newOpp['q_wip'] = 0;
                $newOpp['q_opp_ongoing'] = 0;
                $newOpp['q_opp_remain'] = 0;
                $newOpp['q_opp_order'] = 0;
            }
        }
        return $newOpp;
    }

    private function getBaseProductForecastData($revenueData, $isHistory)
    {
        $forecast = 0;
        $productType = $revenueData['product_value'];
        $brandTypeList = $this->getBaseProductTypeList(RevenueConst::PRODUCT_TYPE_ALL);
        $effectTypeList = $this->getBaseProductTypeList(RevenueConst::PRODUCT_TYPE_EFFECT_ALL);

        /**
         * 品牌预估收入=wip+已下单
         */
        if (\in_array($productType, $brandTypeList)) {
            $forecast = ($revenueData['q_wip'] ?? 0) + ($revenueData['qtd_money'] ?? 0);
        } /**
         * 效果=下单收入*q天数/当前已过天数
         */
        elseif (\in_array($productType, $effectTypeList)) {
            if ($isHistory) {
                $forecast = $revenueData['qtd_money'] ?? 0;
            } else {
                $today = Carbon::today();
                $start = (clone $today)->startOfQuarter();
                $end = (clone $today)->endOfQuarter();
                /**
                 * 季度初到季度末的实际天数需要加1，季度到昨天的天数直接减法就好
                 */
                $forecast = round(($revenueData['qtd_money'] ?? 0) * ($end->diffInDays($start) + 1) / (max($today->diffInDays($start),
                        1)));
            }
        }

        return $forecast;
    }
}


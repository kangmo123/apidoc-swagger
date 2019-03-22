<?php

/**
 * 业绩汇总的方法
 */

namespace App\Services\Revenue\Summary;

use App\Constant\ArchitectConstant;
use App\MicroService\OpportunityClient;
use App\MicroService\RevenueClient;
use Carbon\Carbon;
use App\Constant\RevenueConst;
use App\Utils\TreeUtil;
use App\Constant\ProjectConst;

/**
 * Class ChannelRevenueSummaryService
 * @package App\Services\Task\Summary
 */
class ChannelSummaryService extends BaseSummaryService
{
    public function __construct()
    {
        parent::__construct();
        $this->dataGroupByColumnMap = [
            ArchitectConstant::ARCHITECT_ACCOUNT => [
                'team_id',
                'sale_id',
                'agent_id',
                'client_id',
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
     * 获取客户层级的扁平数据：商机+wip+业绩+预估
     *
     * @param $year
     * @param $quarter
     * @param $agentId
     * @param $saleId
     * @param $teamId
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getFlattenAgentRevenueOpportunityData(
        $year,
        $quarter,
        $agentId,
        $saleId,
        $teamId,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
        $pageNum = self::REVENUE_DEFAULT_PAGE,
        $limit = self::REVENUE_DEFAULT_SIZE
    ) {
        $isHistory = false;
        $date = Carbon::create($year, $quarter * 3, 1);

        if ($date->endOfQuarter() < Carbon::now()) {
            $isHistory = true;
        }

        $oriRevenueDataList = $this->getAgentsRevenueQuarterly(
            $agentId,
            $saleId,
            $teamId,
            $year,
            $quarter,
            $originProductTypeArr,
            $channelType,
            $pageNum,
            $limit
        );
        $opportunityDataList = [];

        $resultData = $this->getCompleteDataByRevenueAndOpp($oriRevenueDataList, $opportunityDataList, $isHistory);

        return $resultData;
    }


    /**
     * 代理商层级数据
     *
     * @param $agentId
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
    protected function getAgentsRevenueQuarterly(
        $agentId,
        $saleId,
        $teamId,
        $year,
        $quarter,
        array $originProductTypeArr,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
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
        $this->fillAgentsAllBaseProductRevenueData($agentId, $saleId, $teamId, $year, $quarter, $channelType, $pageNum,
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
     * @param $agentId
     * @param $saleId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @param array $groupColumnList
     */
    protected function fillAgentsAllBaseProductRevenueData(
        $agentId,
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
        $revenueData = $revenueClient->getAgentsRevenueQuarterly($agentId, $saleId, $teamId,
            $productTypeListStr, $year, $quarter, $channelType, $pageNum, $limit);
        $this->allBaseProductRevenueData = $this->aggregateDataByProductType(empty($revenueData) ? [] : $revenueData,
            $groupColumnList);
    }
}


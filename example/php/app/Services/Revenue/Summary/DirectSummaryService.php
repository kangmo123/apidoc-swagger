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
 * Class DirectSummaryService
 * @package App\Services\Task\Summary
 */
class DirectSummaryService extends BaseSummaryService
{

    public function __construct()
    {
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
        parent::__construct();
    }

    /**
     * 获取客户层级的扁平数据：商机+wip+业绩+预估
     *
     * @param $year
     * @param $quarter
     * @param $clientId
     * @param $saleId
     * @param $teamId
     * @param array $originProductTypeArr
     * @param string $channelType
     * @param int $pageNum
     * @param int $limit
     * @return array
     */
    public function getFlattenClientRevenueOpportunityData(
        $year,
        $quarter,
        $clientId,
        $saleId,
        $teamId,
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
        $oriRevenueDataList = $this->getClientsRevenueQuarterly(
            $clientId,
            $saleId,
            $teamId,
            $year,
            $quarter,
            $originProductTypeArr,
            $channelType,
            $pageNum,
            $limit
        );
        $opportunityDataList = $this->getClientsOpportunityDataQuarterly(
            $saleId,
            $teamId,
            $year,
            $quarter,
            $originProductTypeArr,
            $channelType,
            $pageNum,
            $limit
        );

        $resultData = $this->getCompleteDataByRevenueAndOpp($oriRevenueDataList, $opportunityDataList, $isHistory);

        return $resultData;

    }
}


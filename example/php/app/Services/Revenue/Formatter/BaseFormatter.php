<?php

/**
 *
 */

namespace App\Services\Revenue\Formatter;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Services\Task\TaskService;
use App\Utils\NumberUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 获取业绩服务接口的文件
 *
 * @created time    2018/7/13
 * @author          Glennzhou<glennzhou@tencent.com>
 * @file            RevenueInterfaceService.php
 * @version         $Id$
 */
abstract class BaseFormatter
{
    /**
     * @var array 计算所用的结构树
     */
    protected $archTree = [];

    /**
     * @var array 格式化使用的tree
     */
    protected $formatTree = [];

    public function setFormatTree($formatTree)
    {
        $this->formatTree = $formatTree;
    }

    //品牌
    protected $brandProductTypeList = [
        RevenueConst::PRODUCT_TYPE_VIDEO,
        RevenueConst::PRODUCT_TYPE_NEWS,
        RevenueConst::PRODUCT_TYPE_SNS_CONTRACT,
        RevenueConst::PRODUCT_TYPE_OTHER
    ];

    //效果
    protected $effectProductTypeList = [
        RevenueConst::PRODUCT_TYPE_GDT,
        RevenueConst::PRODUCT_TYPE_MP,
        RevenueConst::PRODUCT_TYPE_SNS_BID
    ];

    /**
     * 给一条记录，附加上需要的字段：计算比例，计算添加任务数据等
     *
     * @param array $originData
     * @param array $taskData
     * @param array $forecastData
     * @param bool $exportFlag
     */
    public function attachAdditionalColumnsWithoutOpp(
        array &$originData,
        array $taskData,
        array $forecastData,
        $exportFlag = false
    ) {
        $this->addTaskColumns($originData, $taskData);
        $this->addForecastColumns($originData, $forecastData);
        $this->addExtendedColumns($originData, $exportFlag);
    }

    /**
     * @author: meyeryan@tencent.com
     *
     * @param array $originData
     * @param array $taskData
     */
    protected function addTaskColumns(array &$originData, array $taskData)
    {
        //获取产品类型
        $productType = $originData['product_value'];
        $productTypeStr = RevenueConst::$productTypeTaskColumnMap[$productType] ?? null;
        //从任务模板填充的任务数据里面获取分产品类型的任务
        foreach (array_values($taskData) as $value) {
            if (isset($value[$productTypeStr])) {
                $originData['q_task'] = $value[$productTypeStr];
                break;
            }
        }
    }

    /**
     * 增加总监预估数据
     * @author: meyeryan@tencent.com
     *
     * @param array $originData
     * @param array $forecastData
     */
    protected function addForecastColumns(array &$originData, array $forecastData)
    {
        $productType = $originData['product_value'];
        $originData['q_task'] = $originData['q_task'] ?? 0;
        $originData['q_forecast'] = $originData['q_forecast'] ?? 0;
        $originData['q_opp'] = $originData['q_opp'] ?? 0;
        if (!\array_key_exists($productType, ProjectConst::$productTypeTaskColumnMap)) {
            $originData['director_fore_money'] = 0;
        } else {
            $forecastKey = ProjectConst::$productTypeTaskColumnMap[$productType];
            $originData['director_fore_money'] = isset($forecastData[$forecastKey]) ? intval($forecastData[$forecastKey]) : 0;
        }
        $originData['forecast_gap'] = $originData['q_forecast'] - $originData['q_task'];
    }

    /**
     * 增加其他比例数据
     *
     * @param array $originData
     * @param $exportFlag
     */
    protected function addExtendedColumns(array &$originData, $exportFlag)
    {
        $productType = $originData['product_value'];

        if ($exportFlag) {
            $unit = ProjectConst::UNIT;
        } else {
            $unit = 1;
        }

        if (\in_array($productType,
            [RevenueConst::PRODUCT_TYPE_SNS_CONTRACT, RevenueConst::PRODUCT_TYPE_OTHER])) {
            $qOpp = $qOppFinishRate = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $qOppFinishRate = NumberUtil::formatRate($originData['q_task'],
                $originData['q_opp']);
            $qOpp = NumberUtil::formatNumber($originData['q_opp'], $unit);
        }

        if ($productType >= RevenueConst::PRODUCT_TYPE_SNS_CONTRACT && $productType <= RevenueConst::PRODUCT_TYPE_OTHER) {
            $forecastFinishRate = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $forecastFinishRate = NumberUtil::formatRate($originData['q_task'], $originData['q_forecast']);
        }

        if ($productType == RevenueConst::PRODUCT_TYPE_OTHER) {
            $directorForeMoneyFinishRate = $directorForeMoneyLost = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $directorForeMoneyLost = NumberUtil::formatNumber($originData['q_task'] - $originData['director_fore_money'],
                $unit);
            $directorForeMoneyFinishRate = NumberUtil::formatRate($originData['q_task'],
                $originData['director_fore_money']);
        }

        if (empty($originData['q_task'])) {
            $forecastGap = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $forecastGap = NumberUtil::formatNumber($originData['q_forecast'] - $originData['q_task'], $unit);
        }

        $qtdFinishRate = NumberUtil::formatRate($originData['q_task'] ?? 0, $originData['qtd_money'] ?? 0);
        $qtdFinishRateFy = NumberUtil::formatRate($originData['q_money_fy'] ?? 0,
            $originData['qtd_money_fy'] ?? 0);

        //相同指标数据计算
        $columns = [
            //本季任务量
            "q_task" => NumberUtil::formatNumber($originData['q_task'] ?? 0, $unit, false),
            //QTD收入
            "qtd_money" => NumberUtil::formatNumber($originData['qtd_money'] ?? 0, $unit),
            //QTD完成率
            "qtd_finish_rate" => $qtdFinishRate,
            //去年同期完成率
            "qtd_finish_rate_fy" => $qtdFinishRateFy,
            //yoy指标
            "yoy" => intval($qtdFinishRate) - intval($qtdFinishRateFy),
            //下单同比
            "q_money_yoy" => NumberUtil::formatRate($originData['qtd_money_fy'] ?? 0, $originData['qtd_money'] ?? 0, 1),
            //下单环比
            "q_money_qoq" => NumberUtil::formatRate($originData['qtd_money_fq'] ?? 0, $originData['qtd_money'] ?? 0, 1),
            //总监预估缺口
            "director_fore_money_lost" => $directorForeMoneyLost,
            //总监预估完成率
            "director_fore_money_finish_rate" => $directorForeMoneyFinishRate,
            //本Q WIP
            "q_wip" => NumberUtil::formatNumber($originData['q_wip'] ?? 0, $unit),
            //本Q 进行中商机
            "q_opp_ongoing" => NumberUtil::formatNumber($originData['q_opp_ongoing'] ?? 0, $unit),
            //本Q 剩余商机
            "q_opp_remain" => NumberUtil::formatNumber($originData['q_opp_remain'] ?? 0, $unit),
            //本Q 商机下单金额
            "q_opp_order" => NumberUtil::formatNumber($originData['q_opp_order'] ?? 0, $unit),
            //forecast
            "q_forecast" => NumberUtil::formatNumber($originData['q_forecast'] ?? 0, $unit),
            //forecast_gap
            "forecast_gap" => $forecastGap,
            //Forecast完成率
            "forecast_finish_rate" => $forecastFinishRate,
            //本Q全部商机
            "q_opp" => $qOpp,
            //所有商机完成率（所有商机+已下单）=（本季度已下单（执行）+所有商机）/本季任务量
            "q_opp_finish_rate" => $qOppFinishRate,
            "qtd_money_fy" => NumberUtil::formatNumber($originData['qtd_money_fy'] ?? 0, $unit),
            "qtd_money_fq" => NumberUtil::formatNumber($originData['qtd_money_fq'] ?? 0, $unit),
            //去年q同期收入
            "q_money_fy" => NumberUtil::formatNumber($originData['q_money_fy'] ?? 0, $unit),
            "q_money_fq" => NumberUtil::formatNumber($originData['q_money_fq'] ?? 0, $unit),
            "product" => RevenueConst::$productTypeNameMap[$productType],
            "product_raw" => RevenueConst::$productTypeNameMap[$productType],
            "arch_name" => RevenueConst::$productTypeNameMap[$productType],
            "qtd_normal_money" => NumberUtil::formatNumber($originData['qtd_normal_money'] ?? 0, $unit),
            "qtd_business_money" => NumberUtil::formatNumber($originData['qtd_business_money'] ?? 0, $unit),
            "qtd_normal_money_fy" => NumberUtil::formatNumber($originData['qtd_normal_money_fy'] ?? 0, $unit),
            "qtd_business_money_fy" => NumberUtil::formatNumber($originData['qtd_business_money_fy'] ?? 0, $unit),
            "director_fore_money" => NumberUtil::formatNumber($originData['director_fore_money'] ?? 0, $unit),
            "mtype" => "all",
        ];

        /**
         * 删指标
         */
        //unset($originData['qtd_normal_money_fy'], $originData['qtd_business_money_fy']);
        $originData = array_merge($originData, $columns);
    }

    /**
     * 扩充商机、opp数据
     * @author: meyeryan@tencent.com
     *
     * @param array $originData
     * @param array $opportunityData
     */
    protected function addOpportunityColumns(array &$originData, array $opportunityData)
    {
        $productType = $originData['product_value'];

        if (array_key_exists($productType, $opportunityData)) {
            $originData['q_opp'] = $opportunityData[$productType]['q_opp'];
            $originData['q_wip'] = $opportunityData[$productType]['q_wip'];
        } else {
            if (in_array($productType,
                [RevenueConst::PRODUCT_TYPE_BRAND_EFFECT, RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER])) {
                $originData['q_opp'] = $opportunityData[RevenueConst::PRODUCT_TYPE_ALL]['q_opp'];
                $originData['q_wip'] = $opportunityData[RevenueConst::PRODUCT_TYPE_ALL]['q_wip'];
            } else {
                $originData['q_opp'] = 0;
                $originData['q_wip'] = 0;
            }
        }
    }

    /**
     * 向下扩展字段的方法
     * @author: meyeryan@tencent.com
     *
     * @param array $revenueData
     * @param string $expandType
     * @return array
     */
    protected function attachExpandData(
        array $revenueData,
        $expandType = RevenueConst::TREE_BRAND_EXPAND_TYPE
    ) {
        if (empty($expandType)) {
            return [];
        }

        if (RevenueConst::TREE_BRAND_EXPAND_TYPE == $expandType) {
            return [
                [
                    'qtd_money' => $revenueData['qtd_normal_money'],
                    'q_money_yoy' => NumberUtil::formatRate($revenueData['qtd_normal_money_fy'],
                        $revenueData['qtd_normal_money'], 1),
                    'product' => RevenueConst::INCOME_TYPE_CG_NAME,
                    'product_raw' => RevenueConst::INCOME_TYPE_CG_NAME,
                    'income_type' => RevenueConst::INCOME_TYPE_CG,
                    'arch_name' => RevenueConst::INCOME_TYPE_CG_NAME,
                    'mtype' => "normal",
                    'product_value' => $revenueData['product_value'],
                ],
                [
                    'qtd_money' => $revenueData['qtd_business_money'],
                    'q_money_yoy' => NumberUtil::formatRate($revenueData['qtd_business_money_fy'],
                        $revenueData['qtd_business_money'], 1),
                    'product' => RevenueConst::INCOME_TYPE_ZS_NAME,
                    'product_raw' => RevenueConst::INCOME_TYPE_ZS_NAME,
                    'income_type' => RevenueConst::INCOME_TYPE_ZS,
                    'arch_name' => RevenueConst::INCOME_TYPE_ZS_NAME,
                    'mtype' => "business",
                    'product_value' => $revenueData['product_value'],
                ]
            ];
        }

        return [];
    }

    /**获取预估收入
     * @author: meyeryan@tencent.com
     *
     * @param $revenueData
     * @param $isHistory
     * @return int
     */
    protected function getForecastData($revenueData, $isHistory)
    {
        $forecast = 0;
        $productType = $revenueData['product_value'];

        /**
         * 品牌预估收入=wip+已下单
         */
        if (\in_array($productType, $this->brandProductTypeList)) {
            $forecast = $revenueData['q_wip'] + $revenueData['qtd_money'];
        } /**
         * 效果=下单收入*q天数/当前已过天数
         */
        elseif (\in_array($productType, $this->effectProductTypeList)) {
            if ($isHistory) {
                $forecast = $revenueData['qtd_money'];
            } else {
                $today = Carbon::today();
                $start = (clone $today)->startOfQuarter();
                $end = (clone $today)->endOfQuarter();
                $forecast = round($revenueData['qtd_money'] * ($end->diffInDays($start) + 1) / ($today->diffInDays($start) + 1));
            }
        }

        return $forecast;
    }

    /**
     * 获取业绩汇总数据
     * @author: meyeryan@tencent.com
     *
     * @param $originData
     * @param array $fields
     * @param array $data
     * @return mixed
     */
    protected function getRevenueSummaryData($originData, array $fields, array $data)
    {
        if (empty($fields)) {
            return $originData;
        }
        foreach ($fields as $field) {
            if (!\array_key_exists($field, $originData)) {
                $originData[$field] = 0;
            }
            $originData[$field] += intval($data[$field]);
        }

        return $originData;
    }
}

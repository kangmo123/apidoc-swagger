<?php
/**
 *业绩数据格式化
 */


namespace App\Services\Revenue\Formatter;

use App\Constant\RevenueConst;


/**
 * Class OverallFormatter
 * PC端业绩格式化器
 * @package frontend\models\revenue\logic\formatter
 */
class ProgressOverallFormatter extends OverallFormatter
{
    /**
     * 进度比较
     *
     * @param $tree
     * @param $taskData
     * @param $forecastData
     * @param $originRevenueOppData
     * @return array
     */
    public function getCompareData($tree, $taskData, $forecastData, $originRevenueOppData)
    {
        $this->resetFlattenData();
        $this->getFlattenData($tree, $taskData, $forecastData, $originRevenueOppData);
        $info = OverallFormatter::$flattenData[RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER];
        $formatData = [
            'tb' => $info['q_money_yoy'],
            'order_rate' => $info['qtd_finish_rate'],
            'order_rate_fy' => $info['qtd_finish_rate_fy'],
            'yoy' => $info['yoy'],
        ];
        return $formatData;
    }

}

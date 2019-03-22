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
class MobileOverallFormatter extends OverallFormatter
{
    /**
     * @var array 移动端的业绩树格式(业绩移动端用的（非计算过程）)
     */
    public static $mobileRevenueOverallTree = [
        RevenueConst::PRODUCT_TYPE_VIDEO,
        RevenueConst::PRODUCT_TYPE_NEWS,
        RevenueConst::PRODUCT_TYPE_SNS_CONTRACT,
        RevenueConst::PRODUCT_TYPE_OTHER,
        RevenueConst::PRODUCT_TYPE_EFFECT_ALL => [
            RevenueConst::PRODUCT_TYPE_GDT,
            RevenueConst::PRODUCT_TYPE_MP,
            RevenueConst::PRODUCT_TYPE_SNS_BID
        ],
        RevenueConst::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => [
            //腾讯视频-非广告-不记业绩
            RevenueConst::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE,
            //腾讯新闻-非广告-不记业绩
            RevenueConst::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE,
            //其他-非广告-不记业绩]
            RevenueConst::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE
        ],
    ];

    /**
     *获取移动端数据
     *
     * @param array $formatTree
     * @param array $taskData
     * @param array $forecastData
     * @return array|mixed
     */
    protected function getFormatData(
        array $formatTree,
        array $taskData,
        array $forecastData
    ) {
        $formatData = [];

        if (empty($formatTree)) {
            return $formatData;
        }

        foreach ($formatTree as $key => $product) {
            if (!is_array($product)) {
                $formatData[] = OverallFormatter::$flattenData[$product] ?? [];
            } else {
                $data = [];
                foreach ($product as $v) {
                    $data[] = OverallFormatter::$flattenData[$v] ?? [];
                }
                $info = OverallFormatter::$flattenData[$key] ?? [];
                $info['children'] = $data;
                $formatData[] = $info;
            }
        }

        return $formatData;
    }

}

<?php
/**
 *业绩数据格式化
 */


namespace App\Services\Revenue\Formatter;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;


/**
 * Class OverallFormatter
 * PC端业绩格式化器
 * @package frontend\models\revenue\logic\formatter
 */
class OverallFormatter extends BaseFormatter
{
    protected static $flattenData = [];

    public function __construct()
    {
        $this->archTree = RevenueConst::$revenueOverallTree;
        $this->formatTree = RevenueConst::$productTypeMergeTree;
    }

    /**
     * 依照架构树计算各层级业绩并按格式树返回最终的业绩树
     *
     * @param array $originRevenueOppData
     * @param array $taskData
     * @param array $forecastData
     * @param $channelType
     * @return array
     */
    public function doOverallFormat(
        array $originRevenueOppData,
        array $taskData,
        array $forecastData,
        $channelType
    ) {
        /**
         * 把整体收入附加下其他数据
         */
        $this->resetFlattenData();
        $this->getFlattenData($this->archTree, $taskData, $forecastData, $originRevenueOppData);
        $data = $this->getFormatData($this->formatTree, $taskData, $forecastData);
        $productRatio = $this->getProductionRatio($channelType);
        return ['records' => $data, 'top' => $productRatio];
    }

    /**
     * 根据pc端的业绩概览树，格式化数据
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
        $data = $this->recursivelyGetPcData($formatTree, $taskData, $forecastData);
        return $data['children'] ?? [];
    }

    /**
     * 递归处理
     *
     * @param array $formatTree
     * @param array $taskData
     * @param array $forecastData
     * @return array|void
     */
    private function recursivelyGetPcData(
        array $formatTree,
        array $taskData,
        array $forecastData
    ) {
        if (empty($formatTree)) {
            return;
        }

        $nodeData = [];
        foreach ($formatTree as $key => $nodeList) {
            /**
             * 既没有普通子节点、也没有叶子节点子集，本身就是叶子节点
             */
            if (isset($nodeList['node'])) {
                $productType = $nodeList['node'];
                $revenueData = isset(OverallFormatter::$flattenData[$productType]) ? OverallFormatter::$flattenData[$productType] : [];
                $nodeData['children'][] = $revenueData;
            } else {
                $node = isset(OverallFormatter::$flattenData[$key]) ? OverallFormatter::$flattenData[$key] : [];
                $subNodeList = isset($nodeList['leaf_nodes_list']) ? $nodeList['leaf_nodes_list'] : $nodeList['children'];
                $children = $this->recursivelyGetPcData($subNodeList, $taskData, $forecastData);
                $nodeData['children'][] = array_merge($node, $children);
            }
        }

        return $nodeData;
    }


    /**
     * 获取扁平化数据
     *
     * @param array $formatTree
     * @param array $taskData
     * @param array $forecastData
     * @param $originRevenueOppData :传递商机、业绩数据以及是否历史，给出汇总好的产品类型=》商机、wip、业绩、预估数据
     */
    protected function getFlattenData(
        array $formatTree,
        array $taskData,
        array $forecastData,
        $originRevenueOppData
    ) {
        if (empty($formatTree)) {
            return;
        }

        foreach ($formatTree as $key => $nodeList) {
            /**
             * 既没有普通子节点、也没有叶子节点子集，本身就是叶子节点
             */
            if (isset($nodeList['node'])) {
                $productType = $nodeList['node'];
                $revenueData = isset($originRevenueOppData[$productType]) ? $originRevenueOppData[$productType] : [];
                $revenueData['product_value'] = $productType;
                $this->attachAdditionalColumnsWithoutOpp($revenueData, $taskData, $forecastData);
                $expandType = isset($nodeList['expand_type']) ? $nodeList['expand_type'] : null;
                /**
                 * 有扩展属性，则添加扩展数据
                 */
                if (!empty($expandType)) {
                    $revenueData['children'] = $this->attachExpandData($revenueData, $expandType);
                }
                OverallFormatter::$flattenData[$revenueData['product_value']] = $revenueData;
            } else {
                $node = isset($originRevenueOppData[$key]) ? $originRevenueOppData[$key] : [];
                $node['product_value'] = $key;
                $this->attachAdditionalColumnsWithoutOpp($node, $taskData, $forecastData);
                $subNodeList = isset($nodeList['leaf_nodes_list']) ? $nodeList['leaf_nodes_list'] : $nodeList['children'];
                OverallFormatter::$flattenData[$node['product_value']] = $node;
                $this->getFlattenData($subNodeList, $taskData, $forecastData, $originRevenueOppData);
            }
        }
    }

    /**
     * 产品比例
     *
     * @param $channelType
     * @return array
     */
    protected function getProductionRatio($channelType)
    {
        $formatData = [];
        $productRatioList = [
            RevenueConst::PRODUCT_TYPE_VIDEO,
            RevenueConst::PRODUCT_TYPE_NEWS,
            RevenueConst::PRODUCT_TYPE_SNS_CONTRACT,
            RevenueConst::PRODUCT_TYPE_OTHER,
            RevenueConst::PRODUCT_TYPE_EFFECT_ALL
        ];
        if (ProjectConst::SALE_CHANNEL_TYPE_CHANNEL == $channelType) {
            $productRatioList = [
                RevenueConst::PRODUCT_TYPE_VIDEO,
                RevenueConst::PRODUCT_TYPE_NEWS,
                RevenueConst::PRODUCT_TYPE_SNS_CONTRACT,
                RevenueConst::PRODUCT_TYPE_OTHER,
            ];
        }
        $productTotal = 0;
        $allTotal = OverallFormatter::$flattenData[RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER]['qtd_money'];
        foreach ($productRatioList as $product) {
            $info = OverallFormatter::$flattenData[$product] ?? [];
            $productTotal += intval($info['qtd_money']);
            $formatData[] = [
                'name' => $info['product_raw'],
                'ratio' => ($allTotal > 0) ? round($info['qtd_money'] / $allTotal * 100) : 100
            ];
        }
        $other = $allTotal - $productTotal;
        if (ProjectConst::SALE_CHANNEL_TYPE_DIRECT == $channelType) {
            $formatData[] = [
                'name' => '其他',
                'ratio' => ($allTotal > 0) ? round($other / $allTotal * 100) : 100
            ];
        }
        return $formatData;
    }

    /**
     *reset
     */
    protected function resetFlattenData()
    {
        OverallFormatter::$flattenData = [];
    }

    /**
     * @return array
     */
    public static function getFlattenOverallData()
    {
        return OverallFormatter::$flattenData;
    }

}

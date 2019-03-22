<?php

namespace App\Services\Account;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\MicroService\ArchitectClient;
use App\MicroService\ClientClient;
use App\MicroService\ClientOrderClient;
use App\Services\Client\ClientService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountService
{
    
    // 业务封装
    
    /**
     * 效果数据(包含效果消耗)
     *
     * @param      $clientId
     * @param      $begin
     * @param      $end
     * @param      $saleId
     * @param      $teamId
     * @param      $channelType
     * @param      $accountId
     * @param      $shortId
     * @param      $sort
     * @param      $page
     * @param      $perPage
     * @param null $confirm
     * @param null $pageInfo
     *
     * @return mixed
     */
    public function getRevenueEffect(
        $clientId,
        $begin,
        $end,
        $saleId,
        $teamId,
        $channelType,
        $accountId,
        $shortId,
        $page,
        $perPage,
        $confirm = null,
        &$pageInfo = null
    )
    {
        // 内部微服务取得数据
        $clientOrderClient = app(ClientOrderClient::class);
        $ret = $clientOrderClient->effectsSearch([
            'client_id'    => $clientId,
            'begin'        => str_replace('-', '', $begin),
            'end'          => str_replace('-', '', $end),
            'sale_id'      => $saleId,
            'team_id'      => $teamId,
            'account_id'   => $accountId,
            'short_id'     => $shortId,
            'channel_type' => $channelType, // 都是服务直客，固定值
            'page'         => $page,        // 透传
            'per_page'     => $perPage, // 透传
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        $records = $ret['data'];
        // 整理数据
        $productPrefix = 'effect_';
        $totalText = 'total'; // 总消耗文案
        $effectTable = [];
        // -- 1. 原始数据填入，微服务转换请求数据准备
        $allBrandIds = [];
        $allProductTypes = [
            RevenueConst::PRODUCT_TYPE_GDT     => RevenueConst::PRODUCT_TYPE_GDT,
            RevenueConst::PRODUCT_TYPE_MP      => RevenueConst::PRODUCT_TYPE_MP,
            RevenueConst::PRODUCT_TYPE_SNS_BID => RevenueConst::PRODUCT_TYPE_SNS_BID,
        ]; // 当前默认写死
        foreach ($records as $record) {
            $tmp = [
                'account_id'     => $record['account_id'], // 账号
                'short_name'     => $record['short_id'], // 客户简称，还需微服务转换处里
                'client_name'    => $record['client_id'], // 客户全称，还需微服务转换处里
                'brand_name'     => $record['brand_id'], // 品牌名称，还需微服务转换处里
                'product_name'   => '', // 产品名称，还需微服务转换处里
                'sale_time_line' => [], // 销售-时间段，还需微服务转换处里
                'team_time_line' => [], // 小组-时间段，还需微服务转换处里
                // 额外留下数据转换前的原始格式给前端，方便制作跳转链接
                'short_id'       => $record['short_id'],
                'client_id'      => $record['client_id'],
                'brand_id'       => $record['brand_id'],
                'sale_data'      => [],
                'team_data'      => [],
            ];
            $allBrandIds[ $record['brand_id'] ] = $record['brand_id'];
            if (!isset($record['cost_data'])) {
                $record['cost_data'] = [];
            }
            foreach ($record['cost_data'] as $costData) {
                $tmp[ $productPrefix . RevenueConst::$productTypeEnglishNameMap[ $costData['product_type'] ] ]
                    = (int)$costData['cost'];
                $allProductTypes[ $costData['product_type'] ] = $costData['product_type'];
            }
            $effectTable[] = $tmp;
        }
        // -- 2. 内部微服务取得数据: 客户信息，包含简称与全称(可从品牌信息获取，因此本段落取消)
        // -- 3. 内部微服务取得数据: 客户信息，包含简称与全称 / 品牌信息，包含品牌产品、销售、小组
        $clientClient = app(ClientClient::class);
        $brandInfo = $clientClient->getBrandInfoBatch("brand_id", array_values($allBrandIds), 2000);
        $channelPrefix = ($channelType == ProjectConst::SALE_CHANNEL_TYPE_DIRECT) ? '' : $channelType . '_';
        $br = ';';
        foreach ($effectTable as $k => &$v) {
            if (!isset($brandInfo[ $v['brand_id'] ])) {
                $v['short_name'] = '-';
                $v['client_name'] = '-';
                $v['brand_name'] = '-';
                $v['product_name'] = '-';
                continue;
            }
            $v['short_name'] = $brandInfo[ $v['brand_id'] ]['client']['short_name'];
            $v['client_name'] = $brandInfo[ $v['brand_id'] ]['client']['client_name'];
            $v['brand_name'] = $brandInfo[ $v['brand_id'] ]['brand_name'];
            $v['product_name'] = $brandInfo[ $v['brand_id'] ]['product_name'];
            $v['sale_data'] = [];
            foreach ($brandInfo[ $v['brand_id'] ][ $channelPrefix . 'sale' ] as $sale) {
                if ($sale['rtx'] != 'administrator') {
                    $v['sale_time_line'][] = $sale['name'] . '(' . $sale['rtx'] . ')' . ' - '
                        . $sale['begin'] . '~' . $sale['end'];
                    $v['sale_data'][] = $sale;
                }
            }
            $v['sale_time_line'] = implode($br, $v['sale_time_line']);
            foreach ($brandInfo[ $v['brand_id'] ][ $channelPrefix . 'team' ] as $team) {
                $v['team_time_line'][] = $team['team_name'] . ' - '
                    . $team['begin'] . '~' . $team['end'];
            }
            $v['team_time_line'] = implode($br, $v['team_time_line']);
            $v['team_data'] = $brandInfo[ $v['brand_id'] ][ $channelPrefix . 'team' ];
        }
        // -- 4. 补全各类型消耗数据以及总消耗
        foreach ($effectTable as $k => &$v) {
            $totalCost = 0;
            foreach ($allProductTypes as $productType) {
                if (!isset($v[ $productPrefix . RevenueConst::$productTypeEnglishNameMap[ $productType ] ])) {
                    $v[ $productPrefix . RevenueConst::$productTypeEnglishNameMap[ $productType ] ] = 0;
                } else {
                    $totalCost += $v[ $productPrefix . RevenueConst::$productTypeEnglishNameMap[ $productType ] ];
                }
            }
            $v[ $productPrefix . $totalText ] = $totalCost;
        }
        // -- 5. 排序与分页(排序功能取消/分页改用微服务透传)
        // -- 6. 额外信息整理: 当前页汇总、消耗字段名称映射、跨栏文案、单位统一(单位取消)
        $mapProductTypes = [];
        $allProductTypes[0] = 0;
        foreach ($effectTable as $k => &$v) {
            foreach ($allProductTypes as $pk => &$pv) {
                $productSuffix = ($pk == 0) ? $totalText : RevenueConst::$productTypeEnglishNameMap[ $pk ];
                $pv += $v[ $productPrefix . $productSuffix ];
            }
        }
        $effectTableTmp = [
            'account_id'     => '当前页汇总',
            'short_name'     => '-',
            'client_name'    => '-',
            'brand_name'     => '',
            'product_name'   => '',
            'sale_time_line' => '-',
            'team_time_line' => '-',
            'short_id'       => '-',
            'client_id'      => '-',
            'brand_id'       => '-',
            'sale_data'      => [],
            'team_data'      => [],
        ];
        foreach ($allProductTypes as $pk => &$pv) {
            $productSuffix = ($pk == 0) ? $totalText : RevenueConst::$productTypeEnglishNameMap[ $pk ];
            $effectTableTmp[ $productPrefix . $productSuffix ] = $pv - $pk; // 初始值需要调整回来
            $mapProductTypes[ $productPrefix . $productSuffix ] =
                ($pk != 0) ? RevenueConst::$productTypeNameMap[ $pk ] . '消耗' : '效果总消耗';
        }
        $data['aggregate'] = $effectTableTmp; // 当前页汇总信息将由前端计算(不过计算逻辑还是保留透传给导出数据)
        $colSpan = $begin . '~' . $end . '（单位：' . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . '）';
        $pageInfo = $ret['page_info']; // 透传
        // -- 7. 后门
        $data['list'] = array_values($effectTable);
        $data['map'] = $mapProductTypes;
        $data['col_span'] = $colSpan;
        if ($confirm != null) {
            $data['original'] = $ret;
        }
        
        return $data;
    }
    
    /**
     * 效果消耗数据导出
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param $saleId
     * @param $teamId
     * @param $channelType
     * @param $accountId
     * @param $shortId
     * @param $sort
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getRevenueEffectExportFile(
        $clientId,
        $begin,
        $end,
        $saleId,
        $teamId,
        $channelType,
        $accountId,
        $shortId
    )
    {
        // 内部微服务取得数据(透过效果数据封装)
        $data = $this->getRevenueEffect(
            $clientId,
            $begin,
            $end,
            $saleId,
            $teamId,
            $channelType,
            $accountId,
            $shortId,
            0,
            0
        );
        $clientTable = (new ClientService())->clientOverviewInfo($clientId);
        $architectClient = app(ArchitectClient::class);
        $sale = $architectClient->getSale(['sale_id' => $saleId]);
        if ($sale['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $sale['msg']);
        }
        $team = $architectClient->getTeam(['team_id' => $teamId]);
        if ($team['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $team['msg']);
        }
        // 整理数据
        // -- 0. 预处理(移除汇总列，加入特定标头)
        $sale = (isset($sale['data']['fullname'])) ? $sale['data']['fullname'] : '';
        if (count($team['data']) == 1) {
            $team = (isset($team['data'][0]['name'])) ? $team['data'][0]['name'] : '';
        } else {
            $team = '';
        }
        $rows = [];
        $rows[] = [
            '客户下单效果消耗数据(筛选条件: '
            . '时间范围: ' . $begin . ' ~ ' . $end
            . ', 客户: ' . $clientTable['client_name']
            . (($sale != '') ? ', 直客销售: ' . $sale : '')
            . (($team != '') ? ', 直客小组: ' . $team : '')
            . ', 数据截止时间: ' . (new ClientService())->getUpdateTime()
            . ', 单位: 千元)'
        ];
        // -- 1. 整理字段名称
        $headers = ['帐号', '客户简称', '客户全称', '品牌-产品名称', '销售负责人-时间段', '客户小组-时间段'];
        $data['map'] = array_reverse($data['map'], true);
        foreach ($data['map'] as $mk => $mv) {
            $headers[] = $mv;
        }
        $rows[] = $headers;
        // -- 2. 整理记录内容
        $br = ';';
        //$data['list'][] = $data['aggregate'];
        foreach ($data['list'] as $k => $v) {
            $tmpRow = [
                $v['account_id'],
                $v['short_name'],
                $v['client_name'],
                $v['brand_name'] . '-' . $v['product_name'],
                str_replace($br, "\n", $v['sale_time_line']),
                str_replace($br, "\n", $v['team_time_line']),
            ];
            foreach ($data['map'] as $mk => $mv) {
                $tmpRow[] = sprintf("%.3f", $v[ $mk ] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO);
            }
            $rows[] = $tmpRow;
        }
        // -- 3. EXCEL格式
        $filename = "客户下单效果消耗数据_" . Carbon::today()->format('YmdHis') . ".xlsx";
        $path = "/tmp/{$filename}";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows);
        //$sheet->getStyle('E1:F10000')->getAlignment()->setWrapText(true); // 允许销售小组换行展示
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        
        // 返回与导出
        return [
            'file'   => $path,
            'name'   => $filename,
            'header' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ];
    }
}

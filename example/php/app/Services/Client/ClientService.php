<?php

namespace App\Services\Client;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\MicroService\ArchitectClient;
use App\MicroService\ClientClient;
use App\MicroService\ClientOrderClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ClientService
{
    
    // 共用业务逻辑
    
    /**
     * 当前用户与旗下所有销售有无服务过该客户的校验
     *
     * @param $clientId
     * @param $begin
     * @param $end
     *
     * @return bool
     */
    public function checkSaleClientPrivilege($clientId, $begin, $end)
    {
        // 特殊处里: 管理员有最高权限
        if (Auth::user()->isOperator()) {
            return true;
        }
        // 缓存
        $user = (Auth::user())->getRtx();
        $redisKey = __FUNCTION__ . '-' . $user . '-' . $clientId;
        if (Redis::exists($redisKey)) {
            //return Redis::get($redisKey);
        }
        
        // 内部微服务取得数据：取出当前用户为管理人的小组 (业务逻辑需要再了解)
        $architectClient = app(ArchitectClient::class);
        $ret = $architectClient->getSaleTeams([
            'sale_id' => $user,
            //'is_owner' => 1,
            // 确定涉及过往组织架构 (但时间变成分钟级别，因此导入缓存)
            //'begin_date' => '2000-01-01', //todo::解决超时比对问题
            //'end_date'   => '2030-12-31',
        ]);
        
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        
        // 整理数据：取出当前用户为管理人的小组
        $teamIds = [];
        foreach ($ret['data'] as $team) {
            $teamIds[] = $team['team_id'];
        }
        // 内部微服务取得数据整理数据：逐个小组取得组织架构树，并萃取所有所属销售
        $rtxList = [];
        foreach ($teamIds as $teamId) {
            $ret = $architectClient->getTeamSaleTree([
                'type'    => 1, // 确认是否有渠道
                'team_id' => $teamId,
            ]);
            $rtxList = array_merge($rtxList, $this->searchArrayByKey($ret, 'rtx'));
        }
        $rtxList = array_values(
            array_map("unserialize", array_unique(array_map("serialize", $rtxList)))
        );
        // 内部微服务取得数据：查出客户在时间范围内的所有「销售」
        $clientClient = app(ClientClient::class);
        $ret = $clientClient->getBrand([
            'client_id' => $clientId,
            'per_page'  => 2000, // 口头确认单一客户不可能超过2000个以上的品牌(并小于等于接口分页最大值)
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        // 整理数据：查出客户在时间范围内的所有「销售」
        $sales = [];
        foreach ($ret['data'] as $brand) {
            // 直客销售
            foreach ($brand['sale'] as $sale) {
                if (str_replace('-', '', $begin) <= str_replace('-', '', $sale['end'])
                    && str_replace('-', '', $sale['begin']) <= str_replace('-', '', $end)) {
                    // 时间比对建立在 start 必定早于 end 的假设下可这样判断
                    $sales[] = $sale['rtx'];
                }
            }
            $sales = array_values(
                array_map("unserialize", array_unique(array_map("serialize", $sales)))
            );
        }
        // 返回
        $result = count(array_intersect($rtxList, $sales)) > 0;
        Redis::setex($redisKey, 60 * 60, $result);
        
        return $result;
    }
    
    // 业务封装
    
    /**
     * 下单归属
     *
     * @param $clientId
     * @param $begin
     * @param $end
     *
     * @return array
     */
    public function userArchitect($clientId, $begin, $end)
    {
        // 内部微服务取得数据
        $clientClient = app(ClientClient::class);
        $ret = $clientClient->getBrand([
            'client_id' => $clientId,
            'per_page'  => 2000, // 口头确认单一客户不可能超过2000个以上的品牌(并小于等于接口分页最大值)
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        // 整理数据
        $data = [
            'sales' => [],
            'teams' => [],
        ];
        foreach ($ret['data'] as $brand) {
            // 直客销售
            foreach ($brand['sale'] as $sale) {
                if (str_replace('-', '', $begin) <= str_replace('-', '', $sale['end'])
                    && str_replace('-', '', $sale['begin']) <= str_replace('-', '', $end)) {
                    // 时间比对建立在 start 必定早于 end 的假设下可这样判断
                    $data['sales'][] = ['rtx' => $sale['rtx'], 'name' => $sale['name'], 'sale_id' => $sale['sale_id']];
                }
            }
            $data['sales'] = array_values(
                array_map("unserialize", array_unique(array_map("serialize", $data['sales'])))
            );
            // 直客小组
            foreach ($brand['team'] as $team) {
                if (str_replace('-', '', $begin) <= str_replace('-', '', $team['end'])
                    && str_replace('-', '', $team['begin']) <= str_replace('-', '', $end)) {
                    // 时间比对建立在 start 必定早于 end 的假设下可这样判断
                    $data['teams'][] = ['team_id' => $team['team_id'], 'team_name' => $team['team_name']];
                }
            }
            $data['teams'] = array_values(
                array_map("unserialize", array_unique(array_map("serialize", $data['teams'])))
            );
        }
        
        // 返回
        return $data;
    }
    
    /**
     * 下单概览: 客户基本信息部份
     *
     * @param $clientId
     *
     * @return array
     */
    public function clientOverviewInfo($clientId)
    {
        // 内部微服务取得数据
        $clientClient = app(ClientClient::class);
        $ret = $clientClient->getClient([
            'client_id' => $clientId,
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        // 整理数据
        if (count($ret['data']) != 1) {
            $data = []; // 不报错，但要返回空数组
        } else {
            $data = [
                'client_name'   => $ret['data'][0]['client_name'],
                'short_name'    => $ret['data'][0]['short_name'],
                'created_at'    => $ret['data'][0]['created_at'],
                'industry_name' => $ret['data'][0]['first_industry_name'],
                'is_express'    => $ret['data'][0]['is_express'],
                'is_ka'         => $ret['data'][0]['is_ka'],
            ];
        }
        
        // 返回
        return $data;
    }
    
    /**
     * 下单概览: 下单基础数据部份
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param $saleId
     * @param $teamId
     * @param $channelType
     *
     * @return array
     */
    public function clientOverviewDetail(
        $clientId,
        $begin,
        $end,
        $saleId,
        $teamId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    )
    {
        // 内部微服务取得数据
        $clientOrderClient = app(ClientOrderClient::class);
        $ret = $clientOrderClient->clientOverview([
            'client_id'    => $clientId,
            'begin'        => str_replace('-', '', $begin),
            'end'          => str_replace('-', '', $end),
            'sale_id'      => $saleId,
            'team_id'      => $teamId,
            'channel_type' => $channelType, // 都是服务直客，固定值
            'per_page'     => 0,        // 该接口可指定0，避免分页处里
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        // 整理数据：图表数据
        $data = [
            'ordered'  => 0,
            'executed' => 0,
        ];
        $productArchi = [];
        $costBusiness = 0; // 招商 (售卖方式饼图中的售卖方式仅此两种)
        $costNormal = 0;   // 常规 (售卖方式饼图中的售卖方式仅此两种)
        // -- 1. 已下单
        if (!isset($ret['data']['order_data'][0]['cost_data'])) {
            $ret['data']['order_data'][0]['cost_data'] = [];
        }
        foreach ($ret['data']['order_data'][0]['cost_data'] as $product) {
            $data['ordered'] += (int)$product['cost'];
            // 产品结构饼图预处里
            if (!isset($productArchi[ $product['product_type'] ])) {
                $productArchi[ $product['product_type'] ] = [
                    'name'  => RevenueConst::$productTypeNameMap[ $product['product_type'] ],
                    'value' => (int)$product['cost'],
                ];
            } else {
                $productArchi[ $product['product_type'] ]['value'] += (int)$product['cost'];
            }
            // 售卖方式饼图预处里
            $costBusiness += (int)$product['cost_business'];
            $costNormal += (int)$product['cost_normal'];
        }
        // -- 2. 已执行
        if (!isset($ret['data']['execute_data'][0]['cost_data'])) {
            $ret['data']['execute_data'][0]['cost_data'] = [];
        }
        foreach ($ret['data']['execute_data'][0]['cost_data'] as $product) {
            $data['executed'] += (int)$product['cost'];
        }
        // -- 3. 产品结构饼图
        $data[ ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI ] = array_values($productArchi);
        // -- 4. 售卖方式饼图
        $data[ ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD ] = [
            [
                'name'  => RevenueConst::INCOME_TYPE_ZS_NAME,
                'value' => $costBusiness,
            ],
            [
                'name'  => RevenueConst::INCOME_TYPE_CG_NAME,
                'value' => $costNormal,
            ],
        ];
        
        // 返回
        return $data;
    }
    
    /**
     * 下单趋势
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param $timeRange
     * @param $dataType
     * @param $dimension
     * @param $saleId
     * @param $teamId
     * @param $channelType
     * @param $confirm
     *
     * @return array
     */
    public function clientTrend(
        $clientId,
        $begin,
        $end,
        $timeRange,
        $dataType,
        $dimension,
        $saleId,
        $teamId,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $confirm = null
    )
    {
        // 内部微服务取得数据
        $clientOrderClient = app(ClientOrderClient::class);
        $ret = $clientOrderClient->clientDetail([
            'client_id'    => $clientId,
            'begin'        => str_replace('-', '', $begin),
            'end'          => str_replace('-', '', $end),
            'sale_id'      => $saleId,
            'team_id'      => $teamId,
            'channel_type' => $channelType, // 都是服务直客，固定值
            'time_range'   => $timeRange,
            'per_page'     => 0,        // 该接口可指定0，避免分页处里
        ]);
        if ($ret['code'] != 0) {
            throw new \RuntimeException("内部微服务调用失败: " . $ret['msg']);
        }
        $records = $ret['data'];
        // 整理数据
        $trendTable = [];
        $totalAggreCol = '整体下单';
        // -- 1. 补完时间粒度
        foreach ($this->getTimeFormatAll($begin, $end, $timeRange) as $timeFormat) {
            $trendTable[ $timeFormat ] = [
                'time_range' => $timeFormat,
                'items'      => [],
            ];
        }
        // -- 2. 时间粒度排序(也可以不做交给前端排)
        $trendTable = $this->sortArrayByKey($trendTable, 'time_range', SORT_ASC);
        // -- 3. 补完(商品/售卖)并取得所有本次查询中的(商品/售卖)
        $allItem = [];
        foreach ($records as $record) {
            $timeFormat = $this->getTimeFormat($record, $timeRange);
            if (!isset($record['cost_data'])) {
                $record['cost_data'] = [];
            }
            foreach ($record['cost_data'] as $costData) {
                switch ($dimension) {
                    case ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI:
                        // 商品
                        if (!isset($trendTable[ $timeFormat ]['items'][ $costData['product_type'] ])) {
                            // 该物件下单数据不存在
                            $trendTable[ $timeFormat ]['items'][ $costData['product_type'] ] = [
                                'name'  => RevenueConst::$productTypeNameMap[ $costData['product_type'] ],
                                'value' => (int)$costData['cost'],
                                'sort'  => RevenueConst::$productTypeSortMap[ $costData['product_type'] ],
                            ];
                            $allItem[] = $costData['product_type']; // 统计所有物件
                        } else {
                            // 该物件下单数据已存在
                            $trendTable[ $timeFormat ]['items'][ $costData['product_type'] ]['value']
                                += (int)$costData['cost'];
                        }
                        break;
                    case ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD:
                        // 售卖
                        if (!isset($trendTable[ $timeFormat ]['items']['cost_normal'])) {
                            // 该物件下单数据不存在
                            $trendTable[ $timeFormat ]['items']['cost_normal'] = [
                                'name'  => RevenueConst::INCOME_TYPE_CG_NAME,
                                'value' => (int)$costData['cost_normal'],
                                'sort'  => '01',
                            ];
                            $allItem[] = 'cost_normal'; // 统计所有物件
                        } else {
                            // 该物件下单数据已存在
                            $trendTable[ $timeFormat ]['items']['cost_normal']['value']
                                += (int)$costData['cost_normal'];
                        }
                        if (!isset($trendTable[ $timeFormat ]['items']['cost_business'])) {
                            // 该物件下单数据不存在
                            $trendTable[ $timeFormat ]['items']['cost_business'] = [
                                'name'  => RevenueConst::INCOME_TYPE_ZS_NAME,
                                'value' => (int)$costData['cost_business'],
                                'sort'  => '02',
                            ];
                            $allItem[] = 'cost_business'; // 统计所有物件
                        } else {
                            // 该物件下单数据已存在
                            $trendTable[ $timeFormat ]['items']['cost_business']['value']
                                += (int)$costData['cost_business'];
                        }
                        break;
                }
            }
        }
        $allItem = array_unique($allItem);
        // -- 4. 补完不同时间粒度下没有下单的(商品/售卖)并加总并排序
        foreach ($trendTable as $k => &$v) {
            $totalCost = 0;
            foreach ($allItem as $p) {
                if (!isset($v['items'][ $p ])) {
                    switch ($dimension) {
                        case ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI:
                            // 商品
                            $v['items'][ $p ] = [
                                'name'  => RevenueConst::$productTypeNameMap[ $p ],
                                'value' => 0,
                                'sort'  => RevenueConst::$productTypeSortMap[ $p ],
                            ];
                            break;
                        case ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD:
                            // 售卖
                            $v['items']['cost_normal'] = [
                                'name'  => RevenueConst::INCOME_TYPE_CG_NAME,
                                'value' => 0,
                                'sort'  => '01',
                            ];
                            $v['items']['cost_business'] = [
                                'name'  => RevenueConst::INCOME_TYPE_ZS_NAME,
                                'value' => 0,
                                'sort'  => '02',
                            ];
                            break;
                    }
                } else {
                    $totalCost += (int)$v['items'][ $p ]['value'];
                }
            }
            $v['items'][] = [
                'name'  => $totalAggreCol,
                'value' => $totalCost,
                'sort'  => '00',
            ];
            $v['items'] = $this->sortArrayByKey($v['items'], 'sort', SORT_ASC);
            $v['items'] = array_values($v['items']);
        }
        // -- 5. 条件控制，依照绝对值做不同时间的下单汇总，依照占比作息同时间的产品占比
        switch ($dataType) {
            case 'value':
                $productSum = [
                    'time_range' => '汇总',
                    'items'      => [],
                ];
                $totalCost = 0;
                foreach ($records as $record) {
                    if (!isset($record['cost_data'])) {
                        $record['cost_data'] = [];
                    }
                    foreach ($record['cost_data'] as $costData) {
                        switch ($dimension) {
                            case ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI:
                                // 商品
                                if (!isset($productSum['items'][ $costData['product_type'] ])) {
                                    // 该物件下单数据不存在
                                    $productSum['items'][ $costData['product_type'] ] = [
                                        'name'  => RevenueConst::$productTypeNameMap[ $costData['product_type'] ],
                                        'value' => (int)$costData['cost'],
                                        'sort'  => RevenueConst::$productTypeSortMap[ $costData['product_type'] ],
                                    ];
                                } else {
                                    // 该物件下单数据已存在
                                    $productSum['items'][ $costData['product_type'] ]['value'] += (int)$costData['cost'];
                                }
                                $totalCost += (int)$costData['cost'];
                                break;
                            case ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD:
                                // 售卖
                                if (!isset($productSum['items']['cost_normal'])) {
                                    // 该物件下单数据不存在
                                    $productSum['items']['cost_normal'] = [
                                        'name'  => RevenueConst::INCOME_TYPE_CG_NAME,
                                        'value' => (int)$costData['cost_normal'],
                                        'sort'  => '01',
                                    ];
                                } else {
                                    // 该物件下单数据已存在
                                    $productSum['items']['cost_normal']['value'] += (int)$costData['cost_normal'];
                                }
                                $totalCost += (int)$costData['cost_normal'];
                                if (!isset($productSum['items']['cost_business'])) {
                                    // 该物件下单数据不存在
                                    $productSum['items']['cost_business'] = [
                                        'name'  => RevenueConst::INCOME_TYPE_ZS_NAME,
                                        'value' => (int)$costData['cost_business'],
                                        'sort'  => '02',
                                    ];
                                } else {
                                    // 该物件下单数据已存在
                                    $productSum['items']['cost_business']['value'] += (int)$costData['cost_business'];
                                }
                                $totalCost += (int)$costData['cost_business'];
                                break;
                        }
                    }
                }
                $productSum['items'][] = [
                    'name'  => $totalAggreCol,
                    'value' => $totalCost,
                    'sort'  => '00',
                ];
                $productSum['items'] = $this->sortArrayByKey($productSum['items'], 'sort', SORT_ASC);
                $productSum['items'] = array_values($productSum['items']);
                $trendTable[] = $productSum;
            //break;
            case 'ratio':
                foreach ($trendTable as $k => &$v) {
                    $total = 0;
                    foreach ($v['items'] as $p) {
                        $total += $p['value'];
                    }
                    $total /= 2; // 已包含整体下单数量，因此此处的总数已经是两倍的整体下单数量，因此要额外计算
                    foreach ($v['items'] as &$pp) {
                        $pp['ratio'] = $pp['value'] / (($total == 0) ? 1 : $total);
                        $pp['ratio'] = sprintf("%.2f%%", $pp['ratio'] * 100);
                    }
                }
                break;
        }
        // -- 6. 后门
        $data = array_values($trendTable);
        if ($confirm != null) {
            $data[] = $ret;
        }
        
        // 返回
        return $data;
    }
    
    /**
     * 下单趋势导出
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param $timeRange
     * @param $dataType
     * @param $dimension
     * @param $saleId
     * @param $teamId
     *
     * @return array
     * @throws
     */
    public function clientTrendExportFile($clientId, $begin, $end, $timeRange, $dataType, $dimension, $saleId, $teamId)
    {
        // 内部微服务取得数据
        $trendTable = $this->clientTrend($clientId, $begin, $end, $timeRange, $dataType, $dimension, $saleId, $teamId);
        $clientTable = $this->clientOverviewInfo($clientId);
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
        $rows = [];
        if ($trendTable[ count($trendTable) - 1 ]['time_range'] == '汇总') {
            unset($trendTable[ count($trendTable) - 1 ]);
        }
        $sale = (isset($sale['data']['fullname'])) ? $sale['data']['fullname'] : '';
        if (count($team['data']) == 1) {
            $team = (isset($team['data'][0]['name'])) ? $team['data'][0]['name'] : '';
        } else {
            $team = '';
        }
        $rows[] = ['下单趋势详细分析数据 (单位：千元)'];
        $rows[] = [''];
        $rows[] = ['时间范围：' . $begin . '~' . $end];
        $rows[] = ['数据截止时间：' . $this->getUpdateTime()];
        $rows[] = ['客户全称：' . $clientTable['client_name']];
        $rows[] = ['客户简称：' . $clientTable['short_name']];
        $rows[] = ['销售：' . $sale];
        $rows[] = ['小组：' . $team];
        $rows[] = [''];
        // -- 1. 整理字段名称
        $headers = ['时间'];
        foreach ($trendTable as $trend) {
            $headers[] = $trend['time_range'];
        }
        $rows[] = $headers;
        // -- 2. 整理记录内容
        $productCnt = isset($trendTable[0]['items']) ? count($trendTable[0]['items']) : 0;
        $timeCnt = count($trendTable);
        $dataKey = '';
        switch ($dataType) {
            case RevenueConst::TREND_DATA_TYPE_VALUE:
                $dataKey = 'value';
                break;
            case RevenueConst::TREND_DATA_TYPE_RATIO:
                $dataKey = 'ratio';
                break;
        }
        for ($i = 0; $i < $productCnt; $i++) {
            $tmpRow = [];
            for ($j = 0; $j < $timeCnt; $j++) {
                // 取出物件标题
                if ($j == 0) {
                    $tmpRow[] = $trendTable[ $j ]['items'][ $i ]['name'];
                }
                $tmpRow[] = ($dataKey != 'value') ? $trendTable[ $j ]['items'][ $i ][ $dataKey ]
                    : sprintf("%.3f", $trendTable[ $j ]['items'][ $i ][ $dataKey ] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO);
            }
            $rows[] = $tmpRow;
        }
        // -- 3. EXCEL格式
        $filename = "客户下单趋势数据_" . Carbon::today()->format('YmdHis') . ".xlsx";
        $path = "/tmp/{$filename}";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows);
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
    
    // 工具函数
    
    /**
     * 递归取得数组中指定 key 的 values
     *
     * @param $array
     * @param $key
     *
     * @return array
     */
    private function searchArrayByKey($array, $key)
    {
        $results = array();
        if (is_array($array)) {
            if (isset($array[ $key ])) {
                $results[] = $array[ $key ];
            }
            foreach ($array as $subArray) {
                $results = array_merge($results, $this->searchArrayByKey($subArray, $key));
            }
        }
        
        return $results;
    }
    
    /**
     * 递归针对多维数组指定 key 排序 values
     *
     * @param $array
     * @param $on
     * @param $order
     *
     * @return array
     */
    private function sortArrayByKey($array, $on, $order = SORT_ASC)
    {
        if (count($array) <= 1) {
            return $array;
        }
        $newArray = array();
        $sortableArray = array();
        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortableArray[ $k ] = $v2;
                        }
                    }
                } else {
                    $sortableArray[ $k ] = $v;
                }
            }
            switch ($order) {
                case SORT_ASC:
                    asort($sortableArray);
                    break;
                case SORT_DESC:
                    arsort($sortableArray);
                    break;
            }
            foreach ($sortableArray as $k => $v) {
                $newArray[ $k ] = $array[ $k ];
            }
        }
        
        return $newArray;
    }
    
    /**
     * 针对用户下单记录以及粒度参数决定展示时间的字段内容
     *
     * @param $record
     * @param $timeRange
     *
     * @return string
     */
    private function getTimeFormat($record, $timeRange)
    {
        $timeFormat = '';
        switch ($timeRange) {
            case RevenueConst::TIME_RANGE_TYPE_DAILY:
                $timeFormat = Carbon::make($record['consume_date'])->format("Y年m月d日");
                break;
            case RevenueConst::TIME_RANGE_TYPE_WEEKLY:
                $timeFormat = Carbon::make($record['start'])->format("ymd")
                    . '-' .
                    Carbon::make($record['end'])->format("ymd");
                break;
            case RevenueConst::TIME_RANGE_TYPE_MONTHLY:
                $timeFormat = Carbon::make($record['start'])->format("Y年m月");
                break;
            case RevenueConst::TIME_RANGE_TYPE_QUARTERLY:
                $date = Carbon::make($record['start']);
                $timeFormat = $date->year . "年Q" . $date->quarter;
                break;
        }
        
        return $timeFormat;
    }
    
    /**
     * 针对用户下单选择的时间区间与粒度归纳出所有时间段的字段
     *
     * @param $begin
     * @param $end
     * @param $timeRange
     *
     * @return string
     */
    private function getTimeFormatAll($begin, $end, $timeRange)
    {
        $timeFormatAll = [];
        $period = CarbonPeriod::create($begin, $end);
        switch ($timeRange) {
            case RevenueConst::TIME_RANGE_TYPE_DAILY:
                foreach ($period as $k => $v) {
                    $timeFormatAll[] = $v->format("Y年m月d日");
                }
                break;
            case RevenueConst::TIME_RANGE_TYPE_WEEKLY:
                $week = [];
                foreach ($period as $k => $v) {
                    $date = Carbon::make($v);
                    $week[] = $date->format("ymd");
                    if ($date->dayOfWeek == 0) {
                        $timeFormatAll[] = $week[0] . '-' . $week[ count($week) - 1 ];
                        $week = [];
                    }
                }
                if (count($week) > 0) {
                    $timeFormatAll[] = $week[0] . '-' . $week[ count($week) - 1 ];
                }
                break;
            case RevenueConst::TIME_RANGE_TYPE_MONTHLY:
                foreach ($period as $k => $v) {
                    $timeFormatAll[] = $v->format("Y年m月");
                }
                break;
            case RevenueConst::TIME_RANGE_TYPE_QUARTERLY:
                foreach ($period as $k => $v) {
                    $date = Carbon::make($v);
                    $timeFormatAll[] = $date->year . "年Q" . $date->quarter;
                }
                break;
        }
        $timeFormatAll = array_unique($timeFormatAll);
        
        return $timeFormatAll;
    }
    
    /**
     * 偏业务逻辑的参数校验: 依照选择时段确定可用时间粒度
     *
     * @param $begin
     * @param $end
     * @param $timeRange
     *
     * @return boolean
     */
    public function getTimeRangeByDate($begin, $end, $timeRange)
    {
        $days = (Carbon::createFromFormat('Y-m-d', $end))->diffInDays(Carbon::createFromFormat('Y-m-d', $begin)) + 1;
        if ($days < 0) {
            return false;
        }
        if ($days >= 0 && $days <= 7) {
            if (!in_array($timeRange, ['', RevenueConst::TIME_RANGE_TYPE_DAILY])) {
                return false;
            }
            
            return ($timeRange == '') ? RevenueConst::TIME_RANGE_TYPE_DAILY : $timeRange;
        }
        if ($days >= 8 && $days <= 31) {
            if (!in_array($timeRange, [
                '',
                RevenueConst::TIME_RANGE_TYPE_DAILY,
                RevenueConst::TIME_RANGE_TYPE_WEEKLY
            ])) {
                return false;
            }
            
            return ($timeRange == '') ? RevenueConst::TIME_RANGE_TYPE_WEEKLY : $timeRange;
        }
        if ($days >= 32 && $days <= 92) {
            if (!in_array($timeRange, [
                '',
                RevenueConst::TIME_RANGE_TYPE_DAILY,
                RevenueConst::TIME_RANGE_TYPE_WEEKLY,
                RevenueConst::TIME_RANGE_TYPE_MONTHLY
            ])) {
                return false;
            }
            
            return ($timeRange == '') ? RevenueConst::TIME_RANGE_TYPE_MONTHLY : $timeRange;
        }
        if ($days >= 93 && $days <= 366) {
            if (!in_array($timeRange, [
                '',
                RevenueConst::TIME_RANGE_TYPE_DAILY,
                RevenueConst::TIME_RANGE_TYPE_WEEKLY,
                RevenueConst::TIME_RANGE_TYPE_MONTHLY,
                RevenueConst::TIME_RANGE_TYPE_QUARTERLY
            ])) {
                return false;
            }
            
            return ($timeRange == '') ? RevenueConst::TIME_RANGE_TYPE_QUARTERLY : $timeRange;
        }
        if ($days >= 367) {
            return false;
        }
        
        return false;
    }
    
    /**
     * 更新时间
     *
     * @param string $channelType
     *
     * @return string
     */
    public function getUpdateTime($channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT)
    {
        /**
         * @var $clientOrderClient ClientOrderClient
         */
        $clientOrderClient = app(ClientOrderClient::class);
        $ret = $clientOrderClient->updateTime(
            ["channel_type" => $channelType]
        );
        $data = $ret['data'] ?? [];
        $upDateTime = $data['update_time'] ?? Carbon::today()->subDay()->format("Y-m-d");
        $ret = Carbon::make($upDateTime)->format("Y-m-d");
        
        return $ret;
    }
}

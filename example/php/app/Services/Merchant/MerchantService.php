<?php

namespace App\Services\Merchant;

use App\Constant\ArchitectConstant;
use App\Constant\MerchantConstant;
use App\Exceptions\API\ValidationFailed;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\MicroService\MerchantClient;
use App\MicroService\OpportunityClient;
use App\Services\BaseArchitectService;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MerchantService
{
    protected $indexGroupConfig = [
        'product' => [
            'group_by' => [
                'product',
            ],
            'fields' => [],
        ],
        'client' => [
            'group_by' => [
                'client_id',
            ],
            'fields' => [
                'client_id',
                'client_name',
            ],
            'extra' => 'merchantClientOpp',
        ],
        'industry' => [
            'group_by' => [
                'first_industry_id',
            ],
            'fields' => [
                'first_industry_id',
                'first_industry_name',
            ],
        ]
    ];

    protected $totalGroupConfig = [
        'product' => [
            'group_by' => [
                'product',
                'period',
            ],
            'fields' => [],
            'chart_format' => [
                'group_key' => 'product',
                'name_key' => 'product_name',
            ],
        ],
        'client' => [
            'group_by' => [
                'client_id',
                'period',
            ],
            'fields' => [
                'client_id',
                'client_name',
            ],
            'chart_format' => [
                'group_key' => 'client_id',
                'name_key' => 'client_name',
            ],
        ],
        'industry' => [
            'group_by' => [
                'first_industry_id',
                'period',
            ],
            'fields' => [
                'first_industry_id',
                'first_industry_name',
            ],
            'chart_format' => [
                'group_key' => 'first_industry_id',
                'name_key' => 'first_industry_name',
            ],
        ]
    ];

    /**
     * @var MerchantClient
     */
    protected $merchantClient;

    /**
     * @var BaseArchitectService
     */
    protected $architectService;

    public function __construct(
        MerchantClient $merchantClient,
        BaseArchitectService $architectService
    ) {
        $this->merchantClient = $merchantClient;
        $this->architectService = $architectService;
    }

    public function topN($period, $n)
    {
        $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
        $archPid = $archInfo['arch_pid'];
        $archId = $archInfo['arch_id'];
        $archType = $archInfo['arch_type'];

        $params = [
            'period' => $period,
            'n' => $n,
        ];
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->topn($params);
        $data = $ret['data'];
        return $data;
    }

    public function index($merchantCode, $period, $archId, $archPid, $archType, $group)
    {
        if (empty($archType)) {
            $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
            $archPid = $archInfo['arch_pid'];
            $archId = $archInfo['arch_id'];
            $archType = $archInfo['arch_type'];
        }
        if (!array_key_exists($group, $this->indexGroupConfig)) {
            $groupBy = '';
            $fields = '';
            $extra = '';
        } else {
            $groupByConfig = $this->indexGroupConfig[$group];
            $groupBy = $groupByConfig['group_by'];
            $fields = $groupByConfig['fields'];
            $extra = $groupByConfig['extra'] ?? '';
            $groupBy = implode(',', $groupBy);
            $fields = implode(',', $fields);
        }
        $params = [
            'merchant_code' => $merchantCode,
            'period' => $period,
            'group_by' => $groupBy,
            'fields' => $fields,
            'sort' => '-cost',
        ];
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->merchants($params);
        $data = $ret['data'];
        /*
        $extraData = [];
        if ($extra) {
            $extraData = call_user_func_array([$this, $extra],
                [$data, $merchantCode, $period, $archId, $archPid, $archType]);
        }
        */
        $rows = [];
        foreach ($data as $datum) {
            $tmp = [];
            foreach ($datum as $k => $v) {
                if ($k == 'product') {
                    $tmp['product_name'] = MerchantConstant::$productDict[$datum['product']];
                }
                $tmp[MerchantConstant::revertField($k)] = $v;
            }
            $rows[] = $tmp;
        }
        return $rows;
    }

    /**
     * 获取给定招商项目、客户所关联的商机
     * @param $merchantCode
     * @param $period
     * @param $clientId
     * @param $archId
     * @param $archPid
     * @param $archType
     * @return array
     */
    public function merchantClientOpp($merchantCode, $period, $clientId, $archId, $archPid, $archType)
    {
        $year = $quarter = null;
        if (!empty($period)) {
            list($year, $quarter) = explode('Q', $period);
        }
        if (empty($archType)) {
            $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
            $archPid = $archInfo['arch_pid'];
            $archId = $archInfo['arch_id'];
            $archType = $archInfo['arch_type'];
        }
        $params = [
            'project_id' => $merchantCode,
            'client_id' => $clientId,
            'group_by' => 'opportunity_id,resource_id,resource_name'
        ];
        if (!empty($year) || !empty($quarter)) {
            $params['year'] = $year;
            $params['quarter'] = $quarter;
        }
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        /**
         * @var OpportunityClient $client
         */
        $client = app()->make(OpportunityClient::class);
        $ret = $client->projectsOpportunity($params);
        $data = $ret['data'];

        $oppId = $oppNameMap = [];
        foreach ($data as $datum) {
            $oppId[] = $datum['opportunity_id'];
        }
        if (!empty($oppId)) {
            $params = [
                'opportunity_id' => implode(',', $oppId),
                'per_page' => count($oppId),
            ];
            $ret = $client->searchOpportunity($params);
            $oppList = $ret['data'];
            foreach ($oppList as $opp) {
                $oppNameMap[$opp['opportunity_id']] = $opp['opp_name'];
            }
        }
        $ret = [];
        foreach ($data as $datum) {
            $oppId = $datum['opportunity_id'];
            if (array_key_exists($oppId, $ret)) {
                $ret[$oppId]['q_forecast'] += $datum['opp_q_forecast'];
                $ret[$oppId]['q_wip'] += $datum['opp_q_wip'];
                $ret[$oppId]['q_opp_ongoing'] += $datum['opp_q_ongoing'];
            } else {
                $ret[$oppId] = [
                    'opportunity_id' => $oppId,
                    'opp_name' => $oppNameMap[$oppId] ?? '',
                    'q_forecast' => $datum['opp_q_forecast'],
                    'q_wip' => $datum['opp_q_wip'],
                    'q_opp_ongoing' => $datum['opp_q_ongoing'],
                    'resource_id' => [],
                    'resource_name' => [],
                ];
            }
            if (!empty($datum['resource_name'])) {
                $ret[$oppId]['resource_id'][$datum['resource_id']] = $datum['resource_id'];
                $ret[$oppId]['resource_name'][$datum['resource_name']] = $datum['resource_name'];
            }
        }
        foreach ($ret as $oppId => &$data) {
            $data['resource_id'] = implode(',', array_values($data['resource_id']));
            $data['resource_name'] = implode(',', array_values($data['resource_name']));
        }
        return array_values($ret);
    }

    public function compare($merchantCode, $period, $archId, $archPid, $archType)
    {
        if (empty($archType)) {
            $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
            $archPid = $archInfo['arch_pid'];
            $archId = $archInfo['arch_id'];
            $archType = $archInfo['arch_type'];
        }
        $groupBy = "";
        $fields = "";
        $totalParams = [
            'merchant_code' => $merchantCode,
            'period' => $period,
            'group_by' => $groupBy,
            'fields' => $fields,
            'sort' => '-cost',
        ];
        $mineParams = $this->attachArchParameter($totalParams, $archId, $archPid, $archType);
        $mineRet = $this->merchantClient->merchants($mineParams);
        $mineData = $mineRet['data'];
        $mineRows = [];
        foreach ($mineData as $datum) {
            $tmp = [];
            foreach ($datum as $k => $v) {
                if ($k == 'product') {
                    $tmp['product_name'] = MerchantConstant::$productDict[$datum['product']];
                }
                $tmp[MerchantConstant::revertField($k)] = $v;
            }
            $mineRows[] = $tmp;
        }
        $totalRet = $this->merchantClient->merchants($totalParams);
        $totalData = $totalRet['data'];
        $totalRows = [];
        foreach ($totalData as $datum) {
            $tmp = [];
            foreach ($datum as $k => $v) {
                if ($k == 'product') {
                    $tmp['product_name'] = MerchantConstant::$productDict[$datum['product']];
                }
                $tmp[MerchantConstant::revertField($k)] = $v;
            }
            $totalRows[] = $tmp;
        }
        $ret = [
            'mine' => $mineRows[0] ?? [],
            'total' => $totalRows[0] ?? [],
        ];
        return $ret;
    }

    public function total($merchantCode, $period, $clientId, $archId, $archPid, $archType, $group, $page, $perPage)
    {
        if (empty($archType)) {
            $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
            $archPid = $archInfo['arch_pid'];
            $archId = $archInfo['arch_id'];
            $archType = $archInfo['arch_type'];
        }
        if (!array_key_exists($group, $this->totalGroupConfig)) {
            $groupBy = "period";
            $fields = "";
            $chartFormat = [];
        } else {
            $groupByConfig = $this->totalGroupConfig[$group];
            $chartFormat = $groupByConfig['chart_format'];
            $groupBy = $groupByConfig['group_by'];
            $fields = $groupByConfig['fields'];
            $groupBy = implode(',', $groupBy);
            $fields = implode(',', $fields);
        }
        $params = [
            'merchant_code' => $merchantCode,
            'group_by' => $groupBy,
            'fields' => $fields,
            'sort' => '-cost',
        ];
        if (!empty($clientId)) {
            $params['client_id'] = implode(',', $clientId);
        }
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->merchants($params);
        $data = $ret['data'];
        $rows = [];
        foreach ($data as $datum) {
            $tmp = [];
            foreach ($datum as $k => $v) {
                if ($k == 'product') {
                    $tmp['product_name'] = MerchantConstant::$productDict[$datum['product']];
                }
                $tmp[MerchantConstant::revertField($k)] = $v;
            }
            $rows[] = $tmp;
        }
        $ret = $this->formatForChart($rows, $page, $perPage, $chartFormat);
        return $ret;
    }

    /**
     * @param $period
     * @param $archId
     * @param $archPid
     * @param $archType
     * @param $product
     * @param $term
     * @param $sort
     * @param $page
     * @param $perPage
     * @return array
     */
    public function query($period, $archId, $archPid, $archType, $product, $term, $sort, $page, $perPage)
    {
        if (empty($archType)) {
            if (empty($period)) {
                //TODO: 问yunyizhang，获取招商项目列表，没传季度、组织架构的话，根据当前Q的组织架构，获取所有季度的数据
            }
            $archInfo = $this->getUserDefaultMerchantTeamAndSale($period);
            $archPid = $archInfo['arch_pid'];
            $archId = $archInfo['arch_id'];
            $archType = $archInfo['arch_type'];
        }
        $fields = ['merchant_code', 'merchant_name', 'merchant_tag'];
        $groupBy = ['merchant_code'];
        $fields = implode(',', $fields);
        $groupBy = implode(',', $groupBy);
        $params = [
            'term' => $term,
            'period' => $period,
            'product' => $product,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
            'fields' => $fields,
            'group_by' => $groupBy,
        ];
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->merchants($params);
        $data = $ret['data'];
        $pageInfo = $ret['page_info'];
        $data = $this->attachMerchantRevenueAdditionalInfo($data, $period, $product, $archId, $archPid, $archType);
        $ret = [
            'code' => 0,
            'msg' => 'success',
            'data' => $data,
            'page_info' => $pageInfo
        ];
        return $ret;
    }

    protected function attachMerchantRevenueAdditionalInfo($revenues, $period, $product, $archId, $archPid, $archType)
    {
        $codes = [];
        foreach ($revenues as $revenue) {
            $codes[] = $revenue['merchant_code'];
        }
        $totalData = $this->getMerchantTotalByCodes($codes, $product, $archId, $archPid, $archType);
        $totalMap = [];
        foreach ($totalData as $totalDatum) {
            $totalMap[$totalDatum['merchant_code']] = $totalDatum;
        }
        $multiSiteIncomeRet = $this->checkMerchantMultiSiteIncome(
            $codes,
            $period,
            $product,
            $archId,
            $archPid,
            $archType
        );
        $ret = [];
        foreach ($revenues as $revenue) {
            $code = $revenue['merchant_code'];
            $tmp = [];
            foreach ($revenue as $k => $v) {
                $tmp[MerchantConstant::revertField($k)] = $v;
            }
            $isMultiSiteIncomeRet = $multiSiteIncomeRet[$code] ?? false;
            $tmp['is_multi_site_income'] = $isMultiSiteIncomeRet;
            $tmp['total'] = $totalMap[$code] ?? [];
            $ret[] = $tmp;
        }
        return $ret;
    }

    protected function getMerchantTotalByCodes($codes, $product, $archId, $archPid, $archType)
    {
        $fields = ['merchant_code'];
        $groupBy = ['merchant_code'];
        $merchantCodes = is_array($codes) ? implode(',', $codes) : $codes;
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $groupBy = is_array($groupBy) ? implode(',', $groupBy) : $groupBy;
        $params = [
            'merchant_code' => $merchantCodes,
            'product' => $product,
            'fields' => $fields,
            'group_by' => $groupBy,
        ];
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->total($params);
        $data = $ret['data'];
        return $data;
    }

    protected function checkMerchantMultiSiteIncome($merchantCodes, $period, $product, $archId, $archPid, $archType)
    {
        $fields = ['merchant_code'];
        $groupBy = ['merchant_code'];
        $merchantCodes = is_array($merchantCodes) ? implode(',', $merchantCodes) : $merchantCodes;
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $groupBy = is_array($groupBy) ? implode(',', $groupBy) : $groupBy;
        $params = [
            'period' => $period,
            'merchant_code' => $merchantCodes,
            'product' => $product,
            'fields' => $fields,
            'group_by' => $groupBy,
        ];
        $params = $this->attachArchParameter($params, $archId, $archPid, $archType);
        $ret = $this->merchantClient->checkMultiSiteIncome($params);
        $data = $ret['data'];
        return $data;
    }

    protected function attachArchParameter($parameters, $archId, $archPid, $archType)
    {
        switch ($archType) {
            case ArchitectConstant::ARCHITECT_SYSTEM:
                break;
            case ArchitectConstant::ARCHITECT_DEPT:
                $parameters['department_id'] = is_array($archId) ? implode(',', $archId) : $archId;
                break;
            case ArchitectConstant::ARCHITECT_AREA:
                $parameters['area_id'] = is_array($archId) ? implode(',', $archId) : $archId;
                break;
            case ArchitectConstant::ARCHITECT_DIRECTOR:
                $parameters['center_id'] = is_array($archId) ? implode(',', $archId) : $archId;
                $parameters['centre_id'] = $parameters['center_id'];
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
                $parameters['team_id'] = is_array($archId) ? implode(',', $archId) : $archId;
                break;
            case ArchitectConstant::ARCHITECT_SALE:
                $parameters['sale_id'] = is_array($archId) ? implode(',', $archId) : $archId;
                $parameters['team_id'] = is_array($archPid) ? implode(',', $archPid) : $archPid;
                break;
            default:
                throw new ValidationFailed("arch_type错误");
        }
        return $parameters;
    }

    /**
     * 获取当前用户默认能看的招商项目的team_id和sale_id
     * 1. 如果是运营, team_id = sale_id = null，表示能看全部的
     * 2. 如果是GM, 获取管理的部门team_id, arch_type = 8, 查询 department_id in arch_id 的数据
     * 3. 如果是片总, 获取管理的片区team_id, arch_type = 1, 查询 area_id in arch_id 的数据
     * 4. 如果是总监, 获取管理的总监组team_id, arch_type = 2, 查询 center_id in arch_id 的数据
     * 5. 如果是组长, 获取管理的小组的team_id, arch_type = 3, 查询 team_id in arch_id 的数据
     * 6. 如果是销售, arch_type = 4, 获取所有sale_id = arch_id 的数据
     * @param $period
     * @return array
     */
    protected function getUserDefaultMerchantTeamAndSale($period)
    {
        /**
         * @var User $user
         */
        $user = Auth::user();
        if ($user->isAdmin() || $user->isOperator()) {
            $archId = $archPid = [];
            return ['arch_id' => $archId, 'arch_pid' => $archPid, 'arch_type' => ArchitectConstant::ARCHITECT_SYSTEM];
        }
        /**
         * @var ArchitectClient $architectClient
         */
        $architectClient = app()->make(ArchitectClient::class);
        $saleId = $user->getSaleId();
        if (empty($period)) {
            $day = new Carbon();
            $period = $day->year . "Q" . $day->quarter;
        }
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $params = [
            'sale_id' => $saleId,
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        //从组织架构微服务中, 获取当前sale所属的team, 取他level最大的team
        $ret = $architectClient->getSaleTeams($params);
        $rows = $ret['data'];
        $minLevel = 100;
        $isLeader = false;
        foreach ($rows as $row) {
            if ($row['is_hidden']) {
                continue;
            }
            if ($row['is_owner']) {
                $isLeader = true;
            }
            if ($row['level'] < $minLevel) {
                $minLevel = $row['level'];
            }
        }
        if (!$isLeader) {
            //是普通销售
            return ['arch_id' => $saleId, 'arch_pid' => [], 'arch_type' => ArchitectConstant::ARCHITECT_SALE];
        }
        $teamIds = [];
        foreach ($rows as $row) {
            if ($row['level'] == $minLevel && $row['is_owner'] == 1) {
                $teamIds[] = $row['team_id'];
            }
        }
        switch ($minLevel) {
            case ArchitectConstant::TEAM_LEVEL_LEADER:
                return ['arch_id' => $teamIds, 'arch_pid' => [], 'arch_type' => ArchitectConstant::ARCHITECT_LEADER];
            case ArchitectConstant::TEAM_LEVEL_DIRECTOR:
                return ['arch_id' => $teamIds, 'arch_pid' => [], 'arch_type' => ArchitectConstant::ARCHITECT_DIRECTOR];
            case ArchitectConstant::TEAM_LEVEL_AREA:
                return ['arch_id' => $teamIds, 'arch_pid' => [], 'arch_type' => ArchitectConstant::ARCHITECT_AREA];
            case ArchitectConstant::TEAM_LEVEL_DEPT:
                return ['arch_id' => $teamIds, 'arch_pid' => [], 'arch_type' => ArchitectConstant::ARCHITECT_DEPT];
            default:
                throw new ValidationFailed($user->getRtx() . "在{$period}中，不属于任何组织架构");
        }
    }

    /**
     * 格式化数据，给趋势图用
     * @param $rows
     * @param $page
     * @param $perPage
     * @param $chartFormat
     * @return array
     */
    public function formatForChart($rows, $page, $perPage, $chartFormat)
    {
        $charts = [];
        $periodsDefaultData = $this->getPeriodsDefaultData($rows);
        $xAxis = array_keys($periodsDefaultData);
        ksort($xAxis);
        $groupKey = $chartFormat['group_key'] ?? 'default';
        $nameKey = $chartFormat['name_key'] ?? '整体';
        $totalData = $validGroup = [];
        foreach ($rows as $row) {
            if (!array_key_exists($groupKey, $row)) {
                $charts[$groupKey]['data'][$row['period']] = $row['qtd_money'];
                $charts[$groupKey]['name'] = $nameKey;
                continue;
            }
            $groupValue = $row[$groupKey];
            $name = $row[$nameKey];
            $charts[$groupValue]['data'][$row['period']] = $row['qtd_money'];
            $charts[$groupValue]['name'] = $name;
            $totalData[$groupValue] = $row['qtd_money'] + ($totalData[$groupValue] ?? 0);
        }

        if (!empty($page) && !empty($perPage)) {
            arsort($totalData);
            $validGroup = array_slice($totalData, ($page - 1) * $perPage, $perPage, true);
        }
        $yAxis = [];
        if (!empty($validGroup)) {
            foreach ($validGroup as $group => $tmp) {
                $info = $charts[$group] ?? [];
                $data = array_merge($periodsDefaultData, $info['data']);
                ksort($data);
                $yAxis[] = [
                    'name' => $info['name'],
                    'data' => array_values($data),
                ];
            }
        } else {
            foreach ($charts as $group => $info) {
                $data = array_merge($periodsDefaultData, $info['data']);
                ksort($data);
                $yAxis[] = [
                    'name' => $info['name'],
                    'data' => array_values($data),
                ];
            }
        }
        return ['x' => $xAxis, 'y' => $yAxis];
    }

    /**
     * 根据返回的数据，获取全部的季度，方便做数据补全操作
     * ['2018Q1' => 0, '2018Q2' => 0, '2018Q3' => 0, '2018Q4' => 0]
     * @param $rows
     * @return array
     */
    protected function getPeriodsDefaultData($rows)
    {
        $minQuarter = $maxQuarter = null;
        $allPeriods = [];
        foreach ($rows as $row) {
            if (!array_key_exists('period', $row)) {
                continue;
            }
            $period = $row['period'];
            list($year, $quarter) = explode('Q', $period);
            $day = Carbon::create($year, 3 * $quarter)->firstOfQuarter();
            if (empty($minQuarter) || $day < $minQuarter) {
                $minQuarter = clone $day;
            }
            if (empty($maxQuarter) || $day > $maxQuarter) {
                $maxQuarter = clone $day;
            }
        }
        while (true) {
            if (empty($minQuarter) || empty($maxQuarter)) {
                break;
            }
            if ($minQuarter > $maxQuarter) {
                break;
            }
            $allPeriods[$minQuarter->year . "Q" . $minQuarter->quarter] = "-";
            $minQuarter->addQuarter();
        }
        return $allPeriods;
    }

    /**
     * 获取招商项目有数据的季度
     * @param $merchantCode
     * @return array
     */
    public function merchantPeriods($merchantCode)
    {
        $params = [
            'merchant_code' => $merchantCode,
            'group_by' => 'period',
            'fields' => '',
        ];
        $ret = $this->merchantClient->merchants($params);
        $data = $ret['data'];
        $rows = [];
        foreach ($data as $datum) {
            $rows[] = $datum['period'];
        }
        sort($rows);
        return $rows;
    }
}

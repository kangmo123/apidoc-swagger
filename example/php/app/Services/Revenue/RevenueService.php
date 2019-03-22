<?php

namespace App\Services\Revenue;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Library\User;
use App\MicroService\HistoryTaskClient;
use App\MicroService\RevenueClient;
use App\MicroService\TaskClient;
use App\Services\Forecast\ForecastService;
use App\Services\Revenue\Formatter\MobileOverallFormatter;
use App\Services\Revenue\Formatter\OverallFormatter;
use App\Services\Revenue\Summary\ChannelSummaryService;
use App\Services\Revenue\Summary\DirectSummaryService;
use App\Utils\NumberUtil;
use App\Utils\TimerUtil;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class ArchitectService
 * @package App\Services\Revenue
 */
class RevenueService
{
    /**
     * @var RevenueClient
     */
    protected $client;

    public function __construct(RevenueClient $client)
    {
        $this->client = $client;
    }

    /**
     * 根据用户所在的team_id, 校验下钻的id是否在管辖的组织架构中
     * @param $drillId
     * @param $teamId
     * @param $year
     * @param $quarter
     * @return bool
     */
    public function checkRevenuePrivilege($drillId, $teamId, $year, $quarter)
    {
        return true;
    }

    public function checkProductPrivilege(User $user, $year, $quarter, $archType, $saleId, $teamId, $channelType)
    {
        /**
         * @var ArchitectService $revenueArchitectService
         */
        $revenueArchitectService = app(ArchitectService::class);
        $userRoles = $revenueArchitectService->getUserRoles($user, $year, $quarter);
        $checkResult = false;

        if (!is_array($userRoles) || empty($userRoles)) {
            return $checkResult;
        }

        foreach ($userRoles as $role) {
            if ($archType == $role['role_type'] && $saleId == $role['sale_id'] && $teamId == $role['team_id'] && $channelType == $role['channel_type']) {
                $checkResult = true;
                break;
            }
        }

        return $checkResult;
    }

    /**
     * @param $year
     * @param $quarter
     * @return array
     */
    public function getProductTree($year, $quarter)
    {
        $date = Carbon::create($year, 3 * $quarter, 1);
        $newTreeDate = Carbon::create(2018, 3 * 1, 1);
        $tree = RevenueConst::$revenueOverallTree;

        if ($date <= $newTreeDate) {
            unset($tree[RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER]['children'][RevenueConst::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT]);
        }

        return $tree;
    }

    /**
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @param $channelType
     * @return array
     */
    public function getFlattenSaleOverallDataQuarterly($year, $quarter, $archType, $saleId, $teamId, $channelType)
    {
        $cacheKeyFmt = 'product_revenue_new_%dQ%d_%d_%s_%s_%s';
        $cacheKey = sprintf($cacheKeyFmt, $year, $quarter, $archType, $saleId, $teamId, $channelType);
        $cacheData = CacheTags()->get($cacheKey);
        if (!empty($cacheData)) {
            Log::info("hit cache. cache key: $cacheKey");
            return $cacheData;
        }

        $id = $saleId;
        switch ($archType) {
            /**
             * 销售业绩，需要根据销售id拿，销售以上层级数据需要根据team id拿
             * 如果销售本身属于A组，他下了一个B组的单，这个时候，在业绩概览的地方应该直接根据team id过滤掉，
             * 在层级数据中，需要把其他组的业绩放到异常业绩
             */
            case ArchitectConstant::ARCHITECT_SALE:
                $pid = $teamId;
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
            case ArchitectConstant::ARCHITECT_DIRECTOR:
            case ArchitectConstant::ARCHITECT_AREA:
            case ArchitectConstant::ARCHITECT_DEPT:
                //saleId = helenluan, $teamId = KA客户部
                $id = $teamId;
                $pid = null;
                break;
            case ArchitectConstant::ARCHITECT_SYSTEM:
                $id = $pid = null;
                break;
            default:
                return [[], [], []];
                break;
        }

        if (ProjectConst::SALE_CHANNEL_TYPE_DIRECT == $channelType) {
            /**
             * @var $summaryService DirectSummaryService
             */
            $summaryService = new DirectSummaryService();
        } else {
            /**
             * @var $summaryService ChannelSummaryService
             */
            $summaryService = new ChannelSummaryService();
        }

        /**
         * 获取pc端需要的所有产品类型
         */
        $treeNodeList = $summaryService->getAllChildrenByProductType(RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER);

        /**
         * 给业绩的所有产品类型，获取所有产品的汇总数据
         */
        $originData = $summaryService->getFlattenSaleRevenueOpportunityData(
            $year,
            $quarter,
            $archType,
            $id,
            $pid,
            $treeNodeList,
            $channelType
        );
        /**
         * 过滤掉销售层级中，业绩对应的小组id不等于销售当前所在小组id的数据
         */
        $originData = array_filter($originData, function ($v) use ($archType, $teamId) {
            if (ArchitectConstant::ARCHITECT_SALE == $archType) {
                return current($v)['team_id'] == $teamId;
            } else {
                return true;
            }
        });

        $revenueOppData = !empty($originData) ? current($originData) : [];
        $taskData = $this->getTaskData($year, $quarter, $archType, $saleId, $teamId, $channelType);
        //getTaskData()返回的结果统一了
        $taskData = (!empty($taskData) && is_array($taskData)) ? current($taskData) : [];
        //获取总监预估数据
        $forecastService = new ForecastService();
        $forecastData = $forecastService->getOverviewForecast($year, $quarter, $archType, $teamId, $channelType);
        $ret = [
            empty($revenueOppData) ? [] : $revenueOppData,
            empty($taskData) ? [] : $taskData,
            empty($forecastData) ? [] : $forecastData,
        ];
        Log::info("put cache. cache key: $cacheKey");
        CacheTags()->put($cacheKey, $ret, 30);
        return $ret;
    }

    /**
     * @param array $originData
     * @param array $taskData
     * @param array $forecastData
     * @param array $tree
     * @param null $mobileSource
     * @param string $channelType
     * @return mixed
     */
    public function formatSaleOverallData(
        array $originData,
        array $taskData,
        array $forecastData,
        array $tree,
        $mobileSource = null,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_CHANNEL
    ) {
        /**
         * 设置概览树
         */
        if (empty($mobileSource)) {
            /**
             * @var $formatter OverallFormatter
             */
            $formatter = app(OverallFormatter::class);
            $formatter->setFormatTree($tree);
        } else {
            /**
             * @var $formatter MobileOverallFormatter
             */
            $formatter = app(MobileOverallFormatter::class);
            $mobileTree = MobileOverallFormatter::$mobileRevenueOverallTree;
            if (ProjectConst::SALE_CHANNEL_TYPE_CHANNEL == $channelType) {
                unset($mobileTree[RevenueConst::PRODUCT_TYPE_EFFECT_ALL], $mobileTree[RevenueConst::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT]);
            }
            $formatter->setFormatTree($mobileTree);
        }
        $revenueDataWithTask = $formatter->doOverallFormat(
            $originData,
            $taskData,
            $forecastData, $channelType
        );
        return $revenueDataWithTask;
    }

    public function getRevenues($archType, $archId, $product, $year, $quarter, $channelType)
    {
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_DEPT:
                $data = $this->getDepartmentRevenues($archId, $product, $year, $quarter, $channelType);
                $uniqueColumn = ['department_id'];
                break;
            case RevenueConst::ARCH_TYPE_AREA:
                $data = $this->getAreaRevenues($archId, $product, $year, $quarter, $channelType);
                $uniqueColumn = ['department_id', 'area_id'];
                break;
            case RevenueConst::ARCH_TYPE_DIRECTOR:
                $data = $this->getDirectorRevenues($archId, $product, $year, $quarter, $channelType);
                $uniqueColumn = ['area_id', 'centre_id'];
                break;
            case RevenueConst::ARCH_TYPE_TEAM:
                $data = $this->getTeamRevenues($archId, $product, $year, $quarter, $channelType);
                $uniqueColumn = ['centre_id', 'team_id'];
                break;
            case RevenueConst::ARCH_TYPE_SALE:
                $data = $this->getSaleRevenues($archId, $product, $year, $quarter, $channelType);
                $uniqueColumn = ['team_id', 'sale_id'];
                break;
            default:
                $data = [];
                $uniqueColumn = [];
        }

        $fields = RevenueConst::$revenueApiCommonField;
        $revenues = [];
        foreach ($data as $row) {
            $uniqueKey = $this->uniqueKey($row, $uniqueColumn);
            $product = $row['product'];
            if (!isset($revenues[$uniqueKey])) {
                $revenues[$uniqueKey] = [
                    'data' => []
                ];
            }
            if (!isset($revenues[$uniqueKey]['data'][$product])) {
                $revenues[$uniqueKey]['data'][$product] = [];
            }
            foreach ($fields as $field) {
                if (!array_key_exists($field, $revenues[$uniqueKey]['data'][$product])) {
                    $revenues[$uniqueKey]['data'][$product][$field] = 0;
                }
                $revenues[$uniqueKey]['data'][$product][$field] += intval($row[$field]);
            }
            foreach ($uniqueColumn as $column) {
                $revenues[$uniqueKey]['data'][$product][$column] = $row[$column];
            }
            $revenues[$uniqueKey]['data'][$product]['unique'] = $uniqueKey;
        }
        return $revenues;
    }

    /**
     * archDetail的原始数据
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @param $productType
     * @param $channelType
     * @return array
     */
    public function getSaleWithArchDataQuarterlyRaw(
        $year,
        $quarter,
        $archType,
        $saleId,
        $teamId,
        $productType,
        $channelType
    ) {
        //原始业绩和商机数据
        $originRevenueData = $this->getRevenueGroupDataQuarterly($year, $quarter, $archType, $saleId, $teamId,
            $productType, $channelType);
        TimerUtil::log('获取业绩和商机数据');

        //真实层级，详情数据输出的是登录者权限往下一层的数据
        $realArchType = $this->getSaleSubLevel($archType);
        //任务数据
        $taskData = $this->getTaskData($year, $quarter, $realArchType, null, null, $channelType);
        //getTaskData()返回的结果统一了
        $taskData = (!empty($taskData) && is_array($taskData)) ? $taskData : [];
        //获取总监预估数据
        //获取总监预估数据
        $forecastService = new ForecastService();
        $forecastData = $forecastService->getOverviewForecast($year, $quarter, $realArchType, $teamId, $channelType);
        TimerUtil::log('获取预估数据');

        return [
            empty($originRevenueData) ? [] : $originRevenueData,
            empty($taskData) ? [] : $taskData,
            empty($forecastData) ? [] : $forecastData,
        ];
    }

    /**
     * 获取业绩组织架构详情数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @param string $productType
     * @param string $channelType
     * @param int $perPage
     * @return array|mixed
     * @throws \common\components\exception\BusinessException
     */
    public function getRevenueGroupDataQuarterly(
        $year,
        $quarter,
        $archType,
        $saleId,
        $teamId,
        $productType = null,
        $channelType = 'direct',
        $perPage = 0
    ) {
        $revenueData = [];
        /**
         * 组织架构类型可能为零，不能进行非空判断
         */
        if (empty($year) || empty($quarter)) {
            return $revenueData;
        }
        //各组织架构层级按照唯一键进行聚合
        $groupStr = null;
        switch ($archType) {
            /**
             * 销售业绩，需要根据销售id拿，销售以上层级数据需要根据team id拿
             * 如果销售本身属于A组，他下了一个B组的单，这个时候，在业绩概览的地方应该直接根据team id过滤掉，
             * 在层级数据中，需要把其他组的业绩放到异常业绩
             */
            case RevenueConst::ARCH_TYPE_SALE:
                $teamId = null;
                break;
            case RevenueConst::ARCH_TYPE_TEAM:
            case RevenueConst::ARCH_TYPE_DIRECTOR:
            case RevenueConst::ARCH_TYPE_AREA:
            case RevenueConst::ARCH_TYPE_DEPT:
                //取部门的片区汇总数据
                // /v1/revenue/areas?department_id=$pid
                $saleId = null;
                break;
            case RevenueConst::ARCH_TYPE_NATION:
                //取国家数据，实际上取得是按各个部门汇聚过的业绩数据
                // /v1/revenue/departments 只有时间参数
                $teamId = $saleId = null;
                break;
        }
        if ($channelType === 'direct') {
            $summaryService = new DirectSummaryService();
        } else {
            $summaryService = new ChannelSummaryService();
        }
        /**
         * 获取pc端需要的所有产品类型
         */
        $treeNodeList = $summaryService->getAllChildrenByProductType($productType);

        //业绩详情页面是上层权限登录看下层的数据
        $realArchType = $this->getSaleSubLevel($archType);
        $revenueData = $summaryService->getFlattenSaleRevenueOpportunityData(
            $year,
            $quarter,
            $realArchType,
            $saleId,
            $teamId,
            $treeNodeList,
            $channelType,
            1,
            $perPage);

        return $revenueData;
    }

    public function getSaleSubLevel($archType)
    {
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_NATION:
                return RevenueConst::ARCH_TYPE_DEPT;
            case RevenueConst::ARCH_TYPE_DEPT:
                return RevenueConst::ARCH_TYPE_AREA;
            case RevenueConst::ARCH_TYPE_AREA:
                return RevenueConst::ARCH_TYPE_DIRECTOR;
            case RevenueConst::ARCH_TYPE_DIRECTOR:
                return RevenueConst::ARCH_TYPE_TEAM;
            case RevenueConst::ARCH_TYPE_TEAM:
                return RevenueConst::ARCH_TYPE_SALE;
            case RevenueConst::ARCH_TYPE_SALE:
                return RevenueConst::ARCH_TYPE_SHORT;
        }
    }

    public function formatSaleWithArchData(
        array $originData,
        $archData,
        $taskData,
        $forecastData,
        $archType,
        $productType,
        $incomeType
    ) {
        $revenueData = [];
        if (empty($originData) && empty($archData)) {
            return $revenueData;
        }

        /**
         * 品牌整体，效果整体，整体收入数据需要多个产品类型进行汇总的逻辑
         * 单个产品类型数据直接项展示的逻辑
         */
        $originData = $this->getSpecificRevenueAndOpportunityByProduct($originData, $archType, $productType);
        /**
         * 获取组织架构本层级下面所有的下层组织架构的ID
         */
        $parentId = null;
        if ($this->higherThanLeaderLevel($archType)) {
            /**
             * 组长以上层级下钻的逻辑
             */
            $subLevelIdentity = 'team_id';
            $identities = array_column($archData, $subLevelIdentity);
        } else {
            /**
             * 组长下钻销售层级的逻辑
             * 获取该组下面的所有销售的ID，并以这些销售为准来展示该组下面的业绩，过滤掉跨组下单的情况
             */
            $subLevelIdentity = 'sale_id';
            $parentId = current($archData)['team_id'];
            $identities = array_column($archData, $subLevelIdentity);
        }
        /**
         * 根据组织架构中获取到下层架构ID来过滤取回的业绩数据
         * 1. 只保留在下层架构中存在的业绩数据
         * 2. 在下层架构中存在，但是没有业绩数据的，先获取任务数据，没有的填充0
         */
        foreach ($identities as $id) {
            //处理业绩数据
            if (!isset($originData[$id])) {
                //对于销售还没有下单的情况，拼上数据进行展示
                $revenueData[$id] = $this->mockRevenueData($productType);
            } else {
                $revenueData[$id] = $originData[$id];
            }
        }

        $finalRevenueData = [];
        /**
         * 循环组装其它所需字段
         */
        foreach ($revenueData as $key => &$revenue) {
            if (!empty($incomeType)) {
                $this->formatIncomeRevenueData($revenue, $incomeType);
                $currentTask = [];
                $currentForecast = [];
            } else {
                $currentTask = isset($taskData[$key]) ? $taskData[$key] : [];
                $currentForecast = isset($forecastData[$key]) ? $forecastData[$key] : [];
            }
            $this->attachAdditionalColumnsWithoutOpp($revenue, $currentTask, $currentForecast);
            $realArchType = $this->getSaleSubLevel($archType);
            $ret = $this->attachArchAdditional($revenue, $key, $archData, $realArchType, $parentId);
            $finalRevenueData[] = $revenue;
        }
        //返回数据进行排序
        $this->buildOrder($finalRevenueData, $archType);
        return $finalRevenueData;
    }

    /**
     * 根据组织架构层级以及产品类型取到相应的业绩及商机预估数据
     * @author: glennzhou@tencent.com
     * @param $originData
     * @param $archType
     * @param $productType
     * @return array
     */
    public function getSpecificRevenueAndOpportunityByProduct($originData, $archType, $productType)
    {
        $revenueData = [];
        if (empty($originData)) {
            return $revenueData;
        }
        $identity = null;
        foreach ($originData as $key => $revenue) {
            $identity = self::getIdentityByArch($archType, $key);
            $revenueData[$identity] = isset($revenue[$productType]) ? $revenue[$productType] : [];
        }
        return $revenueData;
    }

    /**
     * 根据组织架构层级返回唯一建
     * @param $archType
     * @param $key
     * @return mixed|null
     */
    public static function getIdentityByArch($archType, $key)
    {
        $identity = null;
        if (empty($key)) {
            return $identity;
        }
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_NATION:
            case RevenueConst::ARCH_TYPE_DEPT:
            case RevenueConst::ARCH_TYPE_AREA:
            case RevenueConst::ARCH_TYPE_DIRECTOR:
            case RevenueConst::ARCH_TYPE_TEAM:
                $keyAry = explode('_', $key);
                $identity = current($keyAry);
                break;
        }
        return $identity;
    }

    /**
     * @param $archType
     * @return bool
     */
    public function higherThanLeaderLevel($archType)
    {
        $archArr = [
            RevenueConst::ARCH_TYPE_DIRECTOR,
            RevenueConst::ARCH_TYPE_AREA,
            RevenueConst::ARCH_TYPE_DEPT,
            RevenueConst::ARCH_TYPE_NATION
        ];
        return in_array($archType, $archArr);
    }

    /**
     * 填充在组织架构中存在，但是没有下单的组/销售的业绩数据
     * @param $productType
     * @return array
     */
    public function mockRevenueData($productType)
    {
        $revenueData = [];
        foreach (RevenueConst::$revenueApiCommonField as $field) {
            $revenueData[$field] = 0;
        }
        //填充分类产品字段
        $revenueData['product_value'] = $productType;
        return $revenueData;
    }

    /**
     * 针对招商和常规进行特殊处理
     * 1. 将qtd_money替换成qtd_normal_money或者qtd_business_money
     * 2. 将qtd_money_fy替换成qtd_normal_money_fy或者qtd_business_money_fy
     * @author glennzhou@tencent.com
     * @param array $originData
     * @param $incomeType
     * @return array
     */
    public function formatIncomeRevenueData(array &$originData, $incomeType)
    {
        if (empty($originData)) {
            return [];
        }
        if ($incomeType === RevenueConst::INCOME_TYPE_CG) {
            $originData['qtd_money'] = $originData['qtd_normal_money'] ?? 0;
            $originData['qtd_money_fy'] = $originData['qtd_normal_money_fy'] ?? 0;
        } else {
            $originData['qtd_money'] = $originData['qtd_business_money'] ?? 0;
            $originData['qtd_money_fy'] = $originData['qtd_business_money_fy'] ?? 0;
        }
        unset($originData['qtd_money_fq'], $originData['q_money_fq'], $originData['q_money_fy'],
            $originData['qtd_normal_money'], $originData['qtd_normal_money_fy'], $originData['qtd_business_money'],
            $originData['qtd_business_money_fy'], $originData['q_wip'], $originData['q_opp'], $originData['q_forecast']);
        return;
    }

    protected function addTaskColumns(array &$originData, array $taskData)
    {
        //获取产品类型
        $productType = $originData['product_value'];
        if (!array_key_exists($productType, RevenueConst::$productTypeTaskColumnMap)) {
            return;
        }
        $productTypeStr = RevenueConst::$productTypeTaskColumnMap[$productType];
        //从任务模板填充的任务数据里面获取分产品类型的任务
        foreach (array_values($taskData) as $value) {
            if (isset($value[$productTypeStr])) {
                $originData['q_task'] = $value[$productTypeStr];
                break;
            }
        }
    }

    protected function addForecastColumns(array &$originData, array $forecastData)
    {
        $productType = $originData['product_value'];
        if (!\array_key_exists($productType, RevenueConst::$productTypeTaskColumnMap)) {
            $originData['director_fore_money'] = 0;
        } else {
            $forecastKey = RevenueConst::$productTypeTaskColumnMap[$productType];
            $originData['director_fore_money'] = isset($forecastData[$forecastKey]) ? intval($forecastData[$forecastKey]) : 0;
        }
        $originData['forecast_gap'] = ($originData['q_forecast'] ?? 0) - ($originData['q_task'] ?? 0);
    }

    protected function addExtendedColumns(array &$originData)
    {
        $productType = $originData['product_value'];

        if (\in_array($productType,
            [RevenueConst::PRODUCT_TYPE_SNS_CONTRACT, RevenueConst::PRODUCT_TYPE_OTHER])) {
            $qOpp = $qOppFinishRate = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $qOppFinishRate = NumberUtil::formatRate($originData['q_task'] ?? 0,
                $originData['q_opp'] ?? 0);
            $qOpp = NumberUtil::formatNumber($originData['q_opp'] ?? 0);
        }

        if ($productType >= RevenueConst::PRODUCT_TYPE_SNS_CONTRACT && $productType <= RevenueConst::PRODUCT_TYPE_OTHER) {
            $forecastFinishRate = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $forecastFinishRate = NumberUtil::formatRate($originData['q_task'] ?? 0, $originData['q_forecast'] ?? 0);
        }

        if ($productType == RevenueConst::PRODUCT_TYPE_OTHER) {
            $directorForeMoneyFinishRate = $directorForeMoneyLost = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $directorForeMoneyLost = NumberUtil::formatNumber(($originData['q_task'] ?? 0) - $originData['director_fore_money']);
            $directorForeMoneyFinishRate = NumberUtil::formatRate($originData['q_task'] ?? 0,
                $originData['director_fore_money'] ?? 0);
        }

        if (empty($originData['q_task'])) {
            $forecastGap = RevenueConst::REVENUE_DEFAULT_STR;
        } else {
            $forecastGap = NumberUtil::formatNumber($originData['q_forecast'] ?? 0 - $originData['q_task'] ?? 0);
        }

        $qtdFinishRate = NumberUtil::formatRate($originData['q_task'] ?? 0, $originData['qtd_money'] ?? 0);
        $qtdFinishRateFy = NumberUtil::formatRate($originData['q_money_fy'] ?? 0, $originData['qtd_money_fy'] ?? 0);

        //相同指标数据计算
        $columns = [
            //本季任务量
            'q_task' => NumberUtil::formatNumber($originData['q_task'] ?? 0, 1, true),
            //QTD收入
            'qtd_money' => NumberUtil::formatNumber($originData['qtd_money'] ?? 0),
            //QTD完成率
            'qtd_finish_rate' => $qtdFinishRate,
            //去年同期完成率
            'qtd_finish_rate_fy' => $qtdFinishRateFy,
            //yoy指标
            'yoy' => intval($qtdFinishRate) - intval($qtdFinishRateFy),
            //下单同比
            'q_money_yoy' => NumberUtil::formatRate($originData['qtd_money_fy'] ?? 0, $originData['qtd_money'] ?? 0, 1),
            //下单环比
            'q_money_qoq' => NumberUtil::formatRate($originData['qtd_money_fq'] ?? 0, $originData['qtd_money'] ?? 0, 1),
            //总监预估缺口
            'director_fore_money_lost' => $directorForeMoneyLost,
            //总监预估完成率
            'director_fore_money_finish_rate' => $directorForeMoneyFinishRate,
            //本Q WIP
            'q_wip' => NumberUtil::formatNumber($originData['q_wip'] ?? 0),
            //本Q 进行中商机
            'q_opp_ongoing' => NumberUtil::formatNumber($originData['q_opp_ongoing'] ?? 0),
            //本Q 剩余商机
            'q_opp_remain' => NumberUtil::formatNumber($originData['q_opp_remain'] ?? 0),
            //本Q 商机下单金额
            'q_opp_order' => NumberUtil::formatNumber($originData['q_opp_order'] ?? 0),
            //forecast
            'q_forecast' => NumberUtil::formatNumber($originData['q_forecast'] ?? 0),
            //forecast_gap
            'forecast_gap' => $forecastGap,
            //Forecast完成率
            'forecast_finish_rate' => $forecastFinishRate,
            //本Q全部商机
            'q_opp' => $qOpp,
            //所有商机完成率（所有商机+已下单）=（本季度已下单（执行）+所有商机）/本季任务量
            'q_opp_finish_rate' => $qOppFinishRate,
            'qtd_money_fy' => NumberUtil::formatNumber($originData['qtd_money_fy'] ?? 0),
            'qtd_money_fq' => NumberUtil::formatNumber($originData['qtd_money_fq'] ?? 0),
            //去年q同期收入
            'q_money_fy' => NumberUtil::formatNumber($originData['q_money_fy'] ?? 0),
            'q_money_fq' => NumberUtil::formatNumber($originData['q_money_fq'] ?? 0),
            'product' => RevenueConst::$productTypeNameMap[$productType],
            'product_raw' => RevenueConst::$productTypeNameMap[$productType],
            'qtd_normal_money' => NumberUtil::formatNumber($originData['qtd_normal_money'] ?? 0),
            'qtd_business_money' => NumberUtil::formatNumber($originData['qtd_business_money'] ?? 0),
            'qtd_normal_money_fy' => NumberUtil::formatNumber($originData['qtd_normal_money_fy'] ?? 0),
            'qtd_business_money_fy' => NumberUtil::formatNumber($originData['qtd_business_money_fy'] ?? 0),
            'director_fore_money' => NumberUtil::formatNumber($originData['director_fore_money'] ?? 0),
        ];

        /**
         * 删指标
         */
        //unset($originData['qtd_normal_money_fy'], $originData['qtd_business_money_fy']);
        $originData = array_merge($originData, $columns);
    }

    public function attachAdditionalColumnsWithoutOpp(
        array &$originData,
        array $taskData,
        array $forecastData
    ) {
        $this->addTaskColumns($originData, $taskData);
        $this->addForecastColumns($originData, $forecastData);
        $this->addExtendedColumns($originData);
    }

    /**
     * @param array $originData
     * @param $key
     * @param $archData
     * @param $archType
     * @param null $parentId
     * @return bool
     */
    public function attachArchAdditional(array &$originData, $key, $archData, $archType, $parentId = null)
    {
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_SALE:
                $archInfo = [];
                foreach ($archData as $row) {
                    if ($row['sale_id'] == $key) {
                        $archInfo = $row;
                    }
                }
                $originData['arch_id'] = $key;
                $originData['arch_guid'] = Utils::guid();
                $originData['arch_name'] = $archInfo['name'];
                $originData['name'] = $archInfo['name'];
                $originData['arch_name_raw'] = $archInfo['name'];
                $originData['arch_type'] = $archType;
                //销售的上一层架构ID从下钻的数据中获取
                $originData['arch_pid'] = $parentId;
                break;
            case RevenueConst::ARCH_TYPE_TEAM:
            case RevenueConst::ARCH_TYPE_DIRECTOR:
            case RevenueConst::ARCH_TYPE_AREA:
            case RevenueConst::ARCH_TYPE_DEPT:
                $archInfo = [];
                foreach ($archData as $row) {
                    if ($row['team_id'] == $key) {
                        $archInfo = $row;
                    }
                }
                if (empty($archInfo)) {
                    return false;
                }
                $originData['arch_id'] = $key;
                $originData['arch_guid'] = Utils::guid();
                $owner = $archInfo['owner'][0] ?? [];
                if (isset($owner['name'])) {
                    $archName = $archInfo['name'] . '-' . $owner['name'];
                } else {
                    $archName = $archInfo['name'];
                }
                $originData['arch_name'] = $archName;
                $originData['name'] = $archInfo['name'];
                $originData['arch_name_raw'] = $archInfo['name'];
                $originData['arch_type'] = $archType;
                break;
        }
    }

    public function buildOrder(array &$revenueData, $archType)
    {
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_AREA:
            case RevenueConst::ARCH_TYPE_DIRECTOR:
                $revenueData = $this->orderRevenueDataBySemantic($revenueData);
                break;
            case RevenueConst::ARCH_TYPE_TEAM:
                $revenueData = $this->orderRevenueDataByMoney($revenueData);
                break;
        }
    }

    /**
     * 片区数据排序
     *
     * @param $year
     * @param $quarter
     * @param $revenueData
     * @return array
     */
    public function orderRevenueDataByArea($year, $quarter, $revenueData)
    {
        if (empty($revenueData)) {
            return [];
        }
        $data = [];
        foreach ($revenueData as $key => $record) {
            if ($this->getNewArchitectInfo($year, $quarter)) {
                $areaList = RevenueConst::$validNewAreaList;
            } else {
                $areaList = RevenueConst::$validAreaList;
            }

            if (!isset($areaList[$record['name']])) {
                continue;
            }

            $record['sort'] = $areaList[$record['name']];
            $data[$key] = $record;
        }
        $data = Utils::arraySort($data, 'sort', SORT_ASC);
        return $data;
    }

    /**
     * 总监组和小组按照语义排序
     * @param $revenueData
     * @return array
     */
    public function orderRevenueDataBySemantic($revenueData)
    {
        if (empty($revenueData)) {
            return [];
        }
        //未分配组排在最后面
        foreach ($revenueData as $key => $revenueItem) {
            $pos = strpos($revenueItem['name'], '未分配');
            if ($pos !== false) {
                $unAssignItem = current(array_splice($revenueData, $key, 1));
                $unAssignItem['unAssigned'] = true;
                $revenueData[] = $unAssignItem;
            } else {
                $revenueItem['unAssigned'] = false;
            }
        }
        return $revenueData;
    }

    /**
     * 销售按照金额降序排序
     * @param $revenueData
     * @return array
     */
    public function orderRevenueDataByMoney($revenueData)
    {
        if (empty($revenueData)) {
            return [];
        }
        $revenueData = Utils::arraySort($revenueData, 'qtd_money', SORT_DESC);
        return $revenueData;
    }

    public function getShortsClientsDataQuarterly(
        $saleId,
        $teamId,
        $productType,
        $year,
        $quarter,
        $channelType = 'direct'
    ) {
        // 原始客户业绩数据
        $clientsRevenue = $this->getClientsRevenueQuarterly($saleId, $teamId, $productType, $year, $quarter,
            $channelType);
        $clientsRevenue = $this->filtNoQtdMoneyClients($clientsRevenue);
        if ($channelType == 'direct') {
            $clientsRevenue = $this->fillClientsRevenueWithClientInfo($clientsRevenue);
        } else {
            $clientsRevenue = $this->fillClientsRevenueWithAgentInfo($clientsRevenue);
        }
        return $clientsRevenue;
        //return is_array($productType) || empty($clientsRevenue) ? $clientsRevenue : $clientsRevenue[$productType];
    }

    public function getClientsRevenueQuarterly($saleId, $teamId, $products, $year, $quarter, $channelType = 'direct')
    {
        if ($channelType == 'direct') {
            $summaryService = new DirectSummaryService();
        } else {
            $summaryService = new ChannelSummaryService();
        }
        if (!is_array($products)) {
            $products = $summaryService->getAllChildrenByProductType($products);
        }
        if ($channelType == 'direct') {
            $revenueData = $summaryService->getFlattenClientRevenueOpportunityData(
                $year, $quarter, '', $saleId, $teamId, $products, $channelType);
        } else {
            $revenueData = $summaryService->getFlattenAgentRevenueOpportunityData(
                $year, $quarter, '', $saleId, $teamId, $products, $channelType);
        }

        $result = [];
        foreach ($revenueData as $item) {
            foreach ($item as $product => $v) {
                empty($result[$product]) ? $result[$product] = [$v] : $result[$product][] = $v;
            }
        }
        return $result;
    }

    /**
     * 排除客户、简称层级，本Q未下单且上Q、去年同Q、商机等都很小的客户
     * 任何值大于等于其最小值都会返回给前端显示
     *
     * @param $clientsRevenue array
     * @return array
     */
    private function filtNoQtdMoneyClients($clientsRevenue)
    {
        $clientIds = [];
        foreach ($clientsRevenue as $product => &$productClientsRevenue) {
            foreach ($productClientsRevenue as &$revenue) {
                //过滤掉当Q未下单且上Q、去年同Q、当Q商机金额都很小的客户
                if (!array_key_exists('qtd_money', $revenue)) {
                    //补齐没有qtd_money的一些数据，之后要统一补齐
                    $revenue['qtd_money'] = 0;
                    $revenue['qtd_money_fq'] = 0;
                    $revenue['qtd_money_fy'] = 0;
                    $revenue['q_money_fq'] = 0;
                    $revenue['q_money_fy'] = 0;
                    $revenue['qtd_normal_money'] = 0;
                    $revenue['qtd_business_money'] = 0;
                }
                $filted = true;
                foreach (RevenueConst::$filterNoQtdMoneyClients as $key => $minValue) {
                    if ($revenue[$key] >= $minValue) {
                        $filted = false;
                        break;
                    }
                }
                if (!$filted && !in_array($revenue['client_id'], $clientIds)) {
                    $clientIds[] = $revenue['client_id'];
                }
            }
            unset($revenue);
        }
        unset($productClientsRevenue);
        $filtedRevenue = [];
        foreach ($clientsRevenue as $product => $productClientsRevenue) {
            foreach ($productClientsRevenue as $singleRevenue) {
                //被过滤掉的客户不返回前端
                if (!in_array($singleRevenue['client_id'], $clientIds)) {
                    continue;
                }
                if (empty($filtedRevenue[$product])) {
                    $filtedRevenue[$product] = [$singleRevenue];
                } else {
                    $filtedRevenue[$product][] = $singleRevenue;
                }
            }
        }
        return $filtedRevenue;
    }

    /**
     * 在客户层级业绩中关联客户信息
     * @param array $clientsRevenue
     * @return array
     * @throws \common\components\exception\BusinessException
     */
    private function fillClientsRevenueWithClientInfo($clientsRevenue)
    {
        // 业绩微服务接口返回的客户字段是client_id, ClientService接口返回的是Fclient_id
        $clientIds = [];
        foreach ($clientsRevenue as $product => $productClientsRevenue) {
            $productClientIds = array_column($productClientsRevenue, 'client_id');
            $clientIds = array_unique(array_merge($clientIds, $productClientIds));
        }
        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        $clients = $architectService->getClients($clientIds);

        $clientsInfoMap = [];
        foreach ($clients as $client) {
            $clientsInfoMap[$client['client_id']] = $client;
        }

        $clientsRevenueFilled = [];
        foreach ($clientsRevenue as $product => $productClientsRevenue) {
            foreach ($productClientsRevenue as $singleRevenue) {
                if (!array_key_exists($singleRevenue['client_id'],
                    $clientsInfoMap)) {
                    continue;
                }
                $clientInfo = $clientsInfoMap[$singleRevenue['client_id']];
                $singleRevenue['client_name'] = $clientInfo['client_name'];
                $singleRevenue['arch_type'] = RevenueConst::ARCH_TYPE_CLIENT;
                $singleRevenue['arch_guid'] = Utils::guid();
                $singleRevenue['short_name'] = $clientInfo['short_name'];
                $singleRevenue['short_id'] = $clientInfo['short_id'];
                if (empty($clientsRevenueFilled[$product])) {
                    $clientsRevenueFilled[$product] = [$singleRevenue];
                } else {
                    $clientsRevenueFilled[$product][] = $singleRevenue;
                }
            }
        }
        unset($singleRevenue);// 引用应当删除
        unset($saleClientsRevenue);// 引用应当删除
        return $clientsRevenueFilled;
    }

    /**
     * 在客户层级业绩中关联代理商信息
     * @param array $clientsRevenue
     * @return array
     * @throws \common\components\exception\BusinessException
     */
    private function fillClientsRevenueWithAgentInfo($clientsRevenue)
    {
        $clientIds = $agentIds = [];
        foreach ($clientsRevenue as $product => $productClientsRevenue) {
            $productClientIds = array_column($productClientsRevenue, 'client_id');
            $productAgentIds = array_column($productClientsRevenue, 'agent_id');
            $clientIds = array_unique(array_merge($clientIds, $productClientIds));
            $agentIds = array_unique(array_merge($agentIds, $productAgentIds));
        }
        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        $clients = $architectService->getClients($clientIds);
        $agents = $architectService->getAgents($agentIds);

        $clientsInfoMap = $agentsInfoMap = [];
        foreach ($clients as $client) {
            $clientsInfoMap[$client['client_id']] = $client;
        }
        foreach ($agents as $agent) {
            $agentsInfoMap[$agent['agent_id']] = $agent;
        }

        $clientsRevenueFilled = [];
        foreach ($clientsRevenue as $product => $productClientsRevenue) {
            foreach ($productClientsRevenue as $singleRevenue) {
                if (!array_key_exists($singleRevenue['client_id'],
                        $clientsInfoMap) || !array_key_exists($singleRevenue['agent_id'], $agentsInfoMap)) {
                    continue;
                }
                $clientInfo = $clientsInfoMap[$singleRevenue['client_id']];
                $agentInfo = $agentsInfoMap[$singleRevenue['agent_id']];
                $singleRevenue['agent_name'] = $agentInfo['agent_name'] ?? '';
                $singleRevenue['client_name'] = $clientInfo['client_name'] ?? '';
                $singleRevenue['arch_type'] = RevenueConst::ARCH_TYPE_CLIENT;
                $singleRevenue['arch_guid'] = Utils::guid();
                $singleRevenue['short_name'] = $agentInfo['short_name'] ?? '';
                $singleRevenue['short_id'] = $agentInfo['short_id'] ?? '';
                if (empty($clientsRevenueFilled[$product])) {
                    $clientsRevenueFilled[$product] = [$singleRevenue];
                } else {
                    $clientsRevenueFilled[$product][] = $singleRevenue;
                }
            }
        }
        unset($singleRevenue);// 引用应当删除
        unset($saleClientsRevenue);// 引用应当删除
        return $clientsRevenueFilled;
    }

    /**
     * 格式化客户层级和简称层级的数据
     * @param $clientsRevenue
     * @param $incomeType
     * @param $year
     * @param $quarter
     * @param $shortId
     * @param string $channelType
     * @return array
     */
    public function formatShortsClientsDataQuarterly(
        $clientsRevenue,
        $incomeType,
        $year,
        $quarter,
        $shortId,
        $channelType = 'direct'
    ) {
        $revenueList = $this->formatClientsShortsRevenue($clientsRevenue, $incomeType);
        $format = 'Y-m-d';
        $dataDate = Carbon::now()->format($format);
        /**
         * @var RevenueClient $revenueClient
         */
        $revenueClient = app()->make(RevenueClient::class);
        $updateTime = $revenueClient->getRevenueUpdateTime($year, $quarter, $channelType, $format);
        if ($updateTime) {
            $dataDate = Carbon::createFromFormat($format, $updateTime)->format($format);
        }
        //返回数据格式化，添加日期、records
        $resData = [
            'records' => $revenueList,
            'total' => count($revenueList),
            'date' => [
                'data_date' => $dataDate,
                'period' => "{$year}Q{$quarter}"
            ]
        ];
        return $resData;
    }

    public function formatClientsShortsRevenue($clientsRevenue, $incomeType = null)
    {
        //汇总简称维度数据
        $shortsRevenue = $this->sumShortsRevenueData($clientsRevenue, $incomeType);

        foreach ($shortsRevenue as &$revenue) {
            $this->attachAdditionalColumnsWithoutOpp($revenue, [], []);
        }
        unset($revenue);

        switch ($incomeType) {
            case RevenueConst::INCOME_TYPE_CG:
                $qtdMoneyKey = 'qtd_normal_money';
                $qtdMoneyFyKey = 'qtd_normal_money_fy';
                break;
            case RevenueConst::INCOME_TYPE_ZS:
                $qtdMoneyKey = 'qtd_business_money';
                $qtdMoneyFyKey = 'qtd_business_money_fy';
                break;
            default:
                $qtdMoneyKey = 'qtd_money';
                $qtdMoneyFyKey = 'qtd_money_fy';
        }

        // 合并客户数据，整体组装简称和客户数据
        $clientMap = [];
        foreach ($clientsRevenue as $revenue) {
            $clientId = $revenue['client_id'];
            $shortId = $revenue['short_id'];
            $hash = $shortId . "|" . $clientId;
            $revenue['qtd_money'] = $revenue[$qtdMoneyKey] ?? 0;
            $revenue['qtd_money_fy'] = $revenue[$qtdMoneyFyKey] ?? 0;

            if (!array_key_exists($hash, $clientMap)) {
                $clientMap[$hash] = $revenue;
            } else {
                foreach (RevenueConst::$clientsShortsRevenueFieldsToAdd as $key) {
                    if (isset($clientMap[$hash][$key])) {
                        $clientMap[$hash][$key] += $revenue[$key] ?? 0;
                    } else {
                        $clientMap[$hash][$key] = 0;
                    }
                }
            }
        }
        unset($revenue);

        foreach ($clientMap as $hash => $revenue) {
            $shortId = $revenue['short_id'];
            $this->attachAdditionalColumnsWithoutOpp($revenue, [], []);

            if (empty($shortsRevenue[$shortId]['children'])) {
                $shortsRevenue[$shortId]['children'] = [$revenue];
            } else {
                $shortsRevenue[$shortId]['children'][] = $revenue;
                usort($shortsRevenue[$shortId]['children'], [$this, 'cmpRevenue']);
            }
        }

        $shortsRevenue = array_values($shortsRevenue);
        // 对简称维度的数据进行排序
        usort($shortsRevenue, [$this, 'cmpRevenue']);
        return $shortsRevenue;
    }

    public function sumShortsRevenueData($clientsRevenue, $incomeType = null)
    {
        $shortsRevenue = [];
        foreach ($clientsRevenue as $item) {
            $shortId = $item['short_id'];
            $item['arch_guid'] = Utils::guid();
            if (empty($shortsRevenue[$shortId])) {
                unset($item['client_id']);
                unset($item['client_name']);
                $item['arch_type'] = RevenueConst::ARCH_TYPE_SHORT;
                $shortsRevenue[$shortId] = $item;
            } else {
                foreach (RevenueConst::$clientsShortsRevenueFieldsToAdd as $key) {
                    if (isset($shortsRevenue[$shortId][$key])) {
                        $shortsRevenue[$shortId][$key] += $item[$key] ?? 0;
                    } else {
                        $shortsRevenue[$shortId][$key] = 0;
                    }
                }
            }
        }

        if (empty($incomeType)) {
            return $shortsRevenue;
        }

        switch ($incomeType) {
            case RevenueConst::INCOME_TYPE_CG:
                $qtdMoneyKey = 'qtd_normal_money';
                $qtdMoneyFyKey = 'qtd_normal_money_fy';
                break;
            case RevenueConst::INCOME_TYPE_ZS:
                $qtdMoneyKey = 'qtd_business_money';
                $qtdMoneyFyKey = 'qtd_business_money_fy';
                break;
            default:
                $qtdMoneyKey = 'qtd_money';
                $qtdMoneyFyKey = 'qtd_money_fy';
        }

        foreach ($shortsRevenue as &$row) {
            $row['qtd_money'] = $row[$qtdMoneyKey] ?? 0;
            $row['qtd_money_fy'] = $row[$qtdMoneyFyKey] ?? 0;
        }
        unset($row);
        return $shortsRevenue;
    }

    /**
     * 通过收入类型过滤和重算业绩数据, 涉及客户和简称层级
     *
     * @param $totalRevenue array 客户+简称维度业绩数据
     * @param $incomeType int 收入类型: 1-常规, 2-招商
     * @return array 业绩数据
     */
    protected function filterClientsShortsRevenueByIncomeType($totalRevenue, $incomeType)
    {
        foreach ($totalRevenue as &$shortRevenue) {
            foreach ($shortRevenue['children'] as &$clientRevenue) {
                $clientRevenue = $this->reCalculateRevenueAboutIncomeType($clientRevenue, $incomeType);
                $clientRevenue = $this->filterMeaningfulFields($clientRevenue);
            }
            unset($clientRevenue); // 引用应当删除

            $shortRevenue = $this->reCalculateRevenueAboutIncomeType($shortRevenue, $incomeType);
            $shortRevenue = $this->filterMeaningfulFields($shortRevenue);
        }
        unset($shortRevenue); // 引用应当删除
        return $totalRevenue;
    }

    /**
     * 通过收入类型重算业绩数据, 涉及客户和简称层级
     *
     * @param $revenue array 业绩数据
     * @param $incomeType int 收入类型: 1-常规, 2-招商
     * @return array 业绩数据
     */
    protected function reCalculateRevenueAboutIncomeType($revenue, $incomeType)
    {
        if (!in_array($incomeType, [RevenueConst::INCOME_TYPE_CG, RevenueConst::INCOME_TYPE_ZS])) {
            return $revenue;
        }

        if ($incomeType == RevenueConst::INCOME_TYPE_CG) { // 常规
            $revenue['qtd_money'] = $revenue['qtd_normal_money'];
            $revenue['qtd_money_fy'] = $revenue['qtd_normal_money_fy'];
        } else { // 招商
            $revenue['qtd_money'] = $revenue['qtd_business_money'];
            $revenue['qtd_money_fy'] = $revenue['qtd_business_money_fy'];
        }

        $revenue['q_money_yoy'] = NumberUtil::formatRate($revenue['qtd_money_fy'], $revenue['qtd_money'], 1);
        return $revenue;
    }

    /**
     * 过滤业绩数据, 涉及客户和简称层级
     *
     * @param $revenue array 业绩数据
     * @return array 业绩数据
     */
    protected function filterMeaningfulFields($revenue)
    {
        $meanginglessFields = [
            'q_forecast',
            'q_money_fq',
            'q_money_fy',
            'qtd_business_money',
            'qtd_business_money_fy',
            'qtd_normal_money',
            'qtd_normal_money_fy',
            'qtd_finish_rate_fy',
            'q_opp',
            'q_opp_finish_rate',
            'qtd_money_fq',
            'q_money_qoq',
        ];

        foreach ($revenue as $k => $v) {
            if (in_array($k, $meanginglessFields)) {
                $revenue[$k] = '';
            }
        }
        return $revenue;
    }

    /**
     * 通过比较两个业绩的下单收入来进行排序，降序排列
     *
     * @param $revenue1 array
     * @param $revenue2 array
     * @return int
     */
    private function cmpRevenue($revenue1, $revenue2)
    {
        if ($revenue1['qtd_money'] == $revenue2['qtd_money']) {
            return 0;
        }
        return $revenue1['qtd_money'] > $revenue2['qtd_money'] ? -1 : 1;
    }

    public function getDrillArchRevenues(
        $year,
        $quarter,
        $drillId,
        $archType,
        $saleId,
        $product,
        $incomeType,
        $channelType = 'direct'
    ) {
        $cacheKeyFmt = 'archi_revenue_%dQ%d_%s_%d_%d_%d_%s';
        $cacheKey = sprintf($cacheKeyFmt, $year, $quarter, $drillId, $archType, $product, $incomeType, $channelType);
        $cacheData = CacheTags()->get($cacheKey);
        if (!empty($cacheData)) {
            Log::info("hit cache. cache key: $cacheKey");
            return $cacheData;
        }

        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        $archData = $architectService->getArchData($drillId, $year, $quarter, $archType, $channelType);
        TimerUtil::log('获取下级的组织架构');
        //1.基础组织数据()后面chart也要用
        $rawDataCollection = $this->getSaleWithArchDataQuarterlyRaw($year, $quarter, $archType, $saleId,
            $drillId, $product, $channelType);
        TimerUtil::log('获取了业绩，任务，预估数据');
        list($originRevenueData, $taskData, $forecastData) = $rawDataCollection;
        $records = $this->formatSaleWithArchData(
            $originRevenueData,
            $archData,
            $taskData,
            $forecastData,
            $archType,
            $product,
            $incomeType
        );
        TimerUtil::log('格式化');
        Log::info("put cache. cache key: $cacheKey");
        CacheTags()->put($cacheKey, $records, 30);
        return $records;
    }

    /**
     * 获取任务数据
     *
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @return array
     */
    public function getTaskData($year, $quarter, $archType, $saleId, $teamId, $channelType = 'direct')
    {
        $taskData = [];
        /**
         * 组织架构类型可能为零，不能进行非空判断
         */
        if (empty($year) || empty($quarter)) {
            return $taskData;
        }
        if ($year < 2019 && $channelType == 'direct') {
            /**
             * @var $taskProxy HistoryTaskClient
             */
            $taskProxy = app(HistoryTaskClient::class);
        } else {
            /**
             * @var TaskClient $taskProxy
             */
            $taskProxy = app(TaskClient::class);
        }

        $period = "{$year}Q{$quarter}";
        switch ($archType) {
            case ArchitectConstant::ARCHITECT_SALE:
                $taskData = $taskProxy->getSaleTask($saleId, $period, $channelType);
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
                $taskData = $taskProxy->getTeamTask($teamId, $period, $channelType);
                break;
            case ArchitectConstant::ARCHITECT_DIRECTOR:
                $taskData = $taskProxy->getCentreTask($teamId, $period, $channelType);
                break;
            case ArchitectConstant::ARCHITECT_AREA:
                $taskData = $taskProxy->getAreaTask($teamId, $period, $channelType);
                break;
            case ArchitectConstant::ARCHITECT_DEPT:
                $taskData = $taskProxy->getDepartmentTask($teamId, $period, $channelType);
                break;
            case ArchitectConstant::ARCHITECT_SYSTEM:
                $taskData = $taskProxy->getNationTask($period, $channelType);
                break;
        }
        return $taskData;
    }

    /**
     * @param $year
     * @param $quarter
     * @param string $outFormat
     * @param string $channelType
     * @return string
     */
    public function getRevenueUpdateTime(
        $year,
        $quarter,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $outFormat = 'Y-m-d'
    ) {
        $updateTime = $this->client->getRevenueUpdateTime($year,
            $quarter, $channelType, $outFormat);
        return $updateTime;
    }

    /**
     * @param $channelType
     * @return array
     */
    public function getRevenueTimeRange($channelType)
    {
        $range = $this->client->getRevenuePeriod($channelType);
        return $range;
    }

    public function createProductList(
        $clientsRevenue,
        $year,
        $quarter,
        $archType,
        $product,
        $saleId,
        $teamId,
        $shortId,
        $channelType = 'direct'
    ) {
        $productList = $this->getProductsList($clientsRevenue, $product, $shortId, $channelType);
        $taskData = $this->getTaskData($year, $quarter, $archType, $saleId, $teamId, $channelType);
        $task = isset($taskData[$saleId]) ? $taskData[$saleId] : [];
        foreach ($productList as $p => &$revenue) {
            $revenue['q_task'] = $this->getSaleProductTask($task, $p);
            //销售没有总监预估，不用管
            $revenue['director_fore_money'] = 0;
            $revenue['forecast_gap'] = $revenue['q_forecast'] - $revenue['q_task'];
            $this->addExtendedColumns($revenue);
        }
        unset($revenue);
        return array_values($productList);
    }

    protected function getSaleProductTask($task, $product)
    {
        if ($product == RevenueConst::PRODUCT_TYPE_VIDEO) {
            return isset($task['brand']['video']) ? $task['brand']['video'] : 0;
        }
        if ($product == RevenueConst::PRODUCT_TYPE_NEWS) {
            return isset($task['brand']['news']) ? $task['brand']['news'] : 0;
        }
        if ($product == RevenueConst::PRODUCT_TYPE_SNS_CONTRACT) {
            return isset($task['brand']['sns']) ? $task['brand']['sns'] : 0;
        }
        if ($product == RevenueConst::PRODUCT_TYPE_OTHER) {
            return isset($task['brand']['other']) ? $task['brand']['other'] : 0;
        }
        if ($product == RevenueConst::PRODUCT_TYPE_EFFECT_ALL) {
            return isset($task['effect']['effect']) ? $task['effect']['effect'] : 0;
        }
        return 0;
    }

    /**
     * 通过销售下钻的数据，计算分产品的业绩数据
     * @param $revenues
     * @param $product
     * @param $shortId
     * @param string $channelType
     * @return array
     */
    protected function getProductsList($revenues, $product, $shortId, $channelType = 'direct')
    {
        if (empty($revenues)) {
            return [];
        }
        //确定产品树
        $tree = MobileOverallFormatter::$mobileRevenueOverallTree;
        if (ProjectConst::SALE_CHANNEL_TYPE_CHANNEL == $channelType) {
            unset($tree[RevenueConst::PRODUCT_TYPE_EFFECT_ALL], $tree[RevenueConst::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT]);
        }
        $data = [];
        foreach ($revenues as $p => $productRevenues) {
            $p = (int)$p;
            $data[$p] = [
                'qtd_money' => 0,
                'qtd_money_fq' => 0,
                'qtd_money_fy' => 0,
                'q_money_fq' => 0,
                'q_money_fy' => 0,
                'qtd_normal_money' => 0,
                'qtd_business_money' => 0,
                'product_value' => $p,
                'q_opp' => 0,
                'q_wip' => 0,
                'q_forecast' => 0
            ];
            foreach ($productRevenues as $revenue) {
                if (!empty($shortId) && $shortId !== $revenue['short_id']) {
                    continue;
                }
                $data[$p]['qtd_money'] += $revenue['qtd_money'] ?? 0;
                $data[$p]['qtd_money_fq'] += $revenue['qtd_money_fq'] ?? 0;
                $data[$p]['qtd_money_fy'] += $revenue['qtd_money_fy'] ?? 0;
                $data[$p]['q_money_fq'] += $revenue['q_money_fq'] ?? 0;
                $data[$p]['q_money_fy'] += $revenue['q_money_fy'] ?? 0;
                $data[$p]['qtd_normal_money'] += $revenue['qtd_normal_money'] ?? 0;
                $data[$p]['qtd_business_money'] += $revenue['qtd_business_money'] ?? 0;
                $data[$p]['q_opp'] += $revenue['q_opp'] ?? 0;
                $data[$p]['q_wip'] += $revenue['q_wip'] ?? 0;
                $data[$p]['q_forecast'] += $revenue['q_forecast'] ?? 0;
            }
        }
        $ret = [];
        if ($product == RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER) {
            foreach ($tree as $key => $value) {
                if (is_array($value)) {
                    $tmp = $data[$key];
                    $children = [];
                    foreach ($value as $p) {
                        $subTmp = $data[$p];
                        $subTmp['q_task'] = 0;
                        //销售没有总监预估，不用管
                        $subTmp['director_fore_money'] = 0;
                        $subTmp['forecast_gap'] = $subTmp['q_forecast'] - $subTmp['q_task'];
                        $this->addExtendedColumns($subTmp);
                        $children[] = $subTmp;
                    }
                    $tmp['children'] = $children;
                    $ret[$key] = $tmp;
                } else {
                    $tmp = $data[$value];
                    $tmp['children'] = [
                        [
                            'arch_name' => '常规',
                            'income_type' => 1,
                            'mtype' => 'normal',
                            'product' => '常规',
                            'product_raw' => '常规',
                            'product_value' => $value,
                            'q_money_yoy' => '',
                            'qtd_money' => $tmp['qtd_normal_money'],
                        ],
                        [
                            'arch_name' => '招商',
                            'income_type' => 2,
                            'mtype' => 'business',
                            'product' => '招商',
                            'product_raw' => '招商',
                            'product_value' => $value,
                            'q_money_yoy' => '',
                            'qtd_money' => $tmp['qtd_business_money'],
                        ],
                    ];
                    $ret[$value] = $tmp;
                }
            }
        } else {
            $tmp = $data[$product];
            $tmp['children'] = [
                [
                    'arch_name' => '常规',
                    'income_type' => 1,
                    'mtype' => 'normal',
                    'product' => '常规',
                    'product_raw' => '常规',
                    'product_value' => $product,
                    'q_money_yoy' => '',
                    'qtd_money' => $tmp['qtd_normal_money'],
                ],
                [
                    'arch_name' => '招商',
                    'income_type' => 2,
                    'mtype' => 'business',
                    'product' => '招商',
                    'product_raw' => '招商',
                    'product_value' => $product,
                    'q_money_yoy' => '',
                    'qtd_money' => $tmp['qtd_business_money'],
                ],
            ];
            $ret[$product] = $tmp;
        }
        return $ret;
    }

    /**
     * @param $year
     * @param $quarter
     * @return bool
     */
    public function getNewArchitectInfo($year, $quarter)
    {
        $time = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
        $newAreaTime = Carbon::create(2019, 1, 1);
        return $time >= $newAreaTime;
    }
}

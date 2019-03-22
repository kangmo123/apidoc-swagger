<?php

namespace App\Http\Controllers\Merchant;

use App\Constant\ArchitectConstant;
use App\Constant\MerchantConstant;
use App\Constant\RevenueConst;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Common\ConfigService;
use App\Services\Merchant\MerchantService;
use App\Services\Revenue\ArchitectService;
use App\Services\Tree\Tree;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class MerchantController extends Controller
{
    public function periods()
    {
        $today = Carbon::now();
        $currentPeriod = $today->year . "Q" . $today->quarter;
        $today->subQuarter();
        $periods = [];
        for ($i = 0; $i < 3; $i++) {
            $periods[] = $today->year . "Q" . $today->quarter;
            $today->addQuarter();
        }
        $data = [
            'current' => $currentPeriod,
            'periods' => $periods,
        ];
        return $this->success($data);
    }

    public function merchantPeriods($merchantCode)
    {
        //获取这个招商项目的全部数据，按Q
        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $periods = $service->merchantPeriods($merchantCode);
        $today = Carbon::now();
        $currentPeriod = $today->year . "Q" . $today->quarter;
        $data = [
            'current' => $currentPeriod,
            'periods' => $periods,
        ];
        return $this->success($data);
    }

    public function topN(Request $request)
    {
        $topN = $request->input('n', 5);
        $period = $request->input('period');
        if (empty($period)) {
            $today = Carbon::now();
            $period = $today->year . "Q" . $today->quarter;
        }
        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->topN($period, $topN);
        return $this->success($ret);
    }

    public function merchants(Request $request)
    {
        $term = $request->input('term');
        $period = $request->input('period');
        $archId = $request->input('arch_id');
        $archType = $request->input('arch_type');
        $archPid = $request->input('arch_pid');
        $product = $request->input('product', 0);
        $sort = $request->input('sort');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 5);

        if (empty($period)) {
            $day = new Carbon();
            $period = $day->year . "Q" . $day->quarter;
        }

        $sort = parseSortString($sort);
        $convertedSort = [];
        foreach ($sort as $k => $v) {
            $prefix = $v == 'desc' ? '-' : '+';
            $convertedSort[] = $prefix . MerchantConstant::convertField($k);
        }
        $convertedSort = implode(',', $convertedSort);
        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->query($period, $archId, $archPid, $archType, $product, $term, $convertedSort, $page, $perPage);
        return new Response($ret);
    }

    public function index(Request $request, $merchantCode)
    {
        $period = $request->input('period');
        $archId = $request->input('arch_id');
        $archType = $request->input('arch_type');
        $archPid = $request->input('arch_pid');
        $group = $request->input('group_by');

        if (empty($period)) {
            $day = new Carbon();
            $period = $day->year . "Q" . $day->quarter;
        }
        if ($period == 'total') {
            $period = null;
        }
        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->index($merchantCode, $period, $archId, $archPid, $archType, $group);
        return $this->success($ret);
    }

    public function totalDetail(Request $request, $merchantCode)
    {
        $period = $request->input('period');
        $archId = $request->input('arch_id');
        $archType = $request->input('arch_type');
        $archPid = $request->input('arch_pid');
        $group = $request->input('group_by');
        $clientId = $request->input('client_id');
        $page = $request->input('page');
        $perPage = $request->input('per_page');
        $clientId = explode(',', $clientId);

        if (empty($period)) {
            $today = new Carbon();
            $period = $today->year . "Q" . $today->quarter;
        }
        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->total(
            $merchantCode,
            $period,
            $clientId,
            $archId,
            $archPid,
            $archType,
            $group,
            $page,
            $perPage
        );
        return $this->success($ret);
    }

    public function compare(Request $request, $merchantCode)
    {
        $period = $request->input('period');
        $archId = $request->input('arch_id');
        $archType = $request->input('arch_type');
        $archPid = $request->input('arch_pid');
        //$group = $request->input('group_by');

        if (empty($period)) {
            $day = new Carbon();
            $period = $day->year . "Q" . $day->quarter;
        }
        if ($period == 'total') {
            $period = null;
        }

        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->compare($merchantCode, $period, $archId, $archPid, $archType);
        return $this->success($ret);
    }

    public function opp(Request $request)
    {
        $merchantCode = $request->input('merchant_id');
        $clientId = $request->input('client_id');
        $period = $request->input('period');
        $archId = $request->input('arch_id');
        $archType = $request->input('arch_type');
        $archPid = $request->input('arch_pid');
        //$group = $request->input('group_by');

        /**
         * @var MerchantService $service
         */
        $service = app()->make(MerchantService::class);
        $ret = $service->merchantClientOpp($merchantCode, $period, $clientId, $archId, $archPid, $archType);
        return $this->success($ret);
    }

    public function lastModifyTime()
    {
        /**
         * @var ConfigService $configService
         */
        $configService = app()->make(ConfigService::class);
        $key = 'MERCHANT_JOB_RESULT';
        $ret = $configService->getConfig($key);
        $data = $ret[$key] ?? '';
        return $this->success(['last_modify_time' => $data]);
    }

    public function architects(Request $request)
    {
        //TODO: 兼容一下，如果不传team_id和drill_id的时候，默认把登录用户的最高的team都拉出来
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $period = $request->input('period');
        $channelType = $request->input('channel_type');
        $archType = $request->input('arch_type');
        $teamId = $request->input('team_id');
        //$saleId = $request->input('sale_id');
        $drillId = $request->input('drill_id');
        $drillType = $request->input('arch_type_drill');

        if (!empty($period)) {
            list($year, $quarter) = explode('Q', $period);
        }
        if (empty($year) && empty($quarter) && empty($period)) {
            $today = new Carbon();
            $year = $today->year;
            $quarter = $today->quarter;
        }
        if (!empty($drillType)) {
            $archType = $drillType;
        }
        if (empty($drillId)) {
            $drillId = $teamId;
        }
        /**
         * @var User $user
         */
        $user = Auth::user();
        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        if (empty($drillId)) {
            //如果drill_id为空，获取当前用户最高层级的team
            $data = $architectService->getUserHighestArch($user, $year, $quarter);
        } else {
            $data = $architectService->getArchData($drillId, $year, $quarter, $archType, $channelType);
        }

        $records = [];
        foreach ($data as $row) {
            if (array_key_exists('sale_id', $row)) {
                $row['arch_id'] = $row['sale_id'];
                $row['arch_type'] = RevenueConst::ARCH_TYPE_SALE;
                $row['arch_pid'] = $drillId;
                $row['arch_name'] = $row['name'];
                $records[] = $row;
                continue;
            }
            if (array_key_exists('team_id', $row)) {
                unset($row['owner']);
                $level = $row['level'];
                $archType = RevenueConst::$teamLevelToArchType[$level];
                $row['arch_id'] = $row['team_id'];
                $row['arch_type'] = $archType;
                $row['arch_pid'] = $drillId ?? $row['pid'];
                $row['arch_name'] = $row['name'];
                $records[] = $row;
                continue;
            }
        }
        return $this->success(['records' => $records]);
    }

    public function architectsTree(Request $request)
    {
        $period = $request->input('period');
        if (empty($period)) {
            $today = new Carbon();
            $begin = $today->firstOfQuarter()->format('Y-m-d');
            $end = $today->endOfQuarter()->format('Y-m-d');
        } else {
            list($begin, $end) = Utils::getQuarterBeginEnd($period);
        }
        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        $ret = $architectService->getArchitectTree($begin, $end, ArchitectConstant::DIRECT_SALE_TEAM_GUID);
        $data = $ret[0]['children'];
        $tree = new Tree();
        foreach ($data as $architect) {
            $tree->build($architect);
        }
        $nodes = $tree->bfs();
        foreach ($nodes as $node) {
            $level = $node->getLevel();
            $archType = RevenueConst::$teamLevelToArchType[$level];
            $extraData = [
                'arch_id' => $node->getTeamId(),
                'arch_type' => $archType,
                'arch_pid' => $node->getPid(),
                'arch_name' => $node->getName(),
            ];
            $node->setExtraData($extraData);
            $sales = $node->getSales();
            foreach ($sales as $sale) {
                $extraData = [
                    'arch_id' => $sale->getSaleId(),
                    'arch_type' => RevenueConst::ARCH_TYPE_SALE,
                    'arch_pid' => $sale->getParent()->getTeamId(),
                    'arch_name' => $sale->getName(),
                ];
                $sale->setExtraData($extraData);
            }
        }
        /**
         * @var User $user
         */
        $user = Auth::user();
        if ($user->isAdmin() || $user->isOperator()) {
            $data = json_encode($tree);
            return $this->success($data);
        }
        $saleId = $user->getSaleId();
        //记录当前用户能管理的小组和对应的arch_type
        $levelMap = [];
        foreach ($nodes as $node) {
            if ($node->getLeader() && $node->getLeader()->getSaleId() == $saleId) {
                $level = $node->getLevel();
                $archType = RevenueConst::$teamLevelToArchType[$level];
                $levelMap[$archType][] = $node;
            }
            $sales = $node->getSales();
            foreach ($sales as $sale) {
                if ($sale->getSaleId() == $saleId) {
                    $levelMap[RevenueConst::ARCH_TYPE_SALE][] = $sale;
                }
            }
        }
        ksort($levelMap);
        $nodes = array_pop($levelMap);
        $data = json_encode($nodes);
        return $this->success($data);
    }
}

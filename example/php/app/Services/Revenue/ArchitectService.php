<?php

namespace App\Services\Revenue;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\Services\BaseArchitectService;
use App\Services\Common\ConfigService;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class ArchitectService
 * @package App\Services\Revenue
 */
class ArchitectService extends BaseArchitectService
{
    public function __construct(ConfigService $configService, ArchitectClient $architectClient)
    {
        parent::__construct($configService, $architectClient);
    }

    public function getOperatorGroups(User $user)
    {
        $groups = [];
        if ($user->hasPrivilege(RevenueConst::PRI_OPERATOR_DIRECT)) {
            $groups[] = [
                'team_id' => BaseArchitectService::$operatorGroupTeamMap[self::TYPE_DIRECT],
                'channel_type' => ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
                'sale_id' => ArchitectConstant::DIRECT_LEADER_SALE_ID,
                'role_type' => RevenueConst::ARCH_TYPE_NATION,
                'name' => ArchitectConstant::DIRECT_TEAM_NAME,
            ];
        }
        if ($user->hasPrivilege(RevenueConst::PRI_OPERATOR_CHANNEL)) {
            $groups[] = [
                'team_id' => self::$operatorGroupTeamMap[self::TYPE_CHANNEL],
                'channel_type' => ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                'sale_id' => ArchitectConstant::CHANNEL_LEADER_SALE_ID,
                'role_type' => RevenueConst::ARCH_TYPE_NATION,
                'name' => ArchitectConstant::CHANNEL_TEAM_NAME,
            ];
        }
        return $groups;
    }

    /**
     * @param User $user
     * @param $year
     * @param $quarter
     * @return array
     */
    public function getUserRoles(User $user, $year, $quarter)
    {
        // 用户是管理员
        if ($user->isOperator()) {
            $userRoles = $this->getOperatorGroups($user);
        } else {
            $teams = $this->getSaleTeamsInfo($user, $year, $quarter);
            $userRoles = $this->formatSaleRoles($user, $teams);
        }
        return $userRoles;
    }

    /**
     * 获取用户小组信息
     *
     * @param User $user
     * @param $year
     * @param $quarter
     * @return array
     */
    public function getSaleTeamsInfo(User $user, $year, $quarter)
    {
        /**
         * @var ArchitectClient $architectClient
         */
        list($begin, $end) = Utils::getQuarterBeginEnd("{$year}Q{$quarter}");
        $params = [
            'sale_id' => $user->getRtx(),
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        $architectClient = app(ArchitectClient::class);
        $ret = $architectClient->getSaleTeams($params);
        return isset($ret['data']) ? $ret['data'] : [];
    }

    /**
     * @param User $user
     * @param $teams
     * @return array
     */
    protected function formatSaleRoles(User $user, $teams)
    {
        $roleList = [];
        if (!empty($teams)) {
            $ret = [];
            foreach ($teams as $team) {
                $teamLevel = $team['level'];
                $teamId = $team['team_id'];
                // 处理角色，默认为销售
                $role = ArchitectConstant::ARCHITECT_SALE;

                if ($team['is_owner']) {
                    switch ($teamLevel) {
                        case ArchitectConstant::TEAM_LEVEL_AREA:
                            $role = ArchitectConstant::ARCHITECT_AREA;
                            break;
                        case ArchitectConstant::TEAM_LEVEL_DIRECTOR:
                            $role = ArchitectConstant::ARCHITECT_DIRECTOR;
                            break;
                        case ArchitectConstant::TEAM_LEVEL_LEADER:
                            $role = ArchitectConstant::ARCHITECT_LEADER;
                            break;
                        case ArchitectConstant::TEAM_LEVEL_DEPT:
                            $role = ArchitectConstant::ARCHITECT_DEPT;
                            break;
                        default:
                            $role = ArchitectConstant::ARCHITECT_NONE;
                            break;
                    }
                }

                // 组装数据
                $ret[$teamId] = [
                    'is_owner' => $team['is_owner'],
                    'role_type' => $role,
                    'level' => $teamLevel,
                    'team_id' => $teamId,
                    'sale_id' => $user->getSaleId(),
                    'team_name' => $team['name'],
                    'pid' => $team['pid'],
                    'name' => "{$user->getName()}({$team['name']})",
                    'channel_type' => (ArchitectConstant::TEAM_TYPE_SALE == $team['type']) ? ProjectConst::SALE_CHANNEL_TYPE_DIRECT : ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                ];
            }

            $info = [];
            if (!empty($ret)) {
                /**
                 * 特殊逻辑：
                 * 一个人同时在一个组织架构下有多个角色，只取最高的角色，干掉其他的，
                 * 例如：陈世洪既是总监，又是自己中心的小组组长，则干掉组长角色
                 */
                foreach ($ret as $key => $data) {
                    if (!\array_key_exists($data['pid'], $ret)) {
                        unset($data['pid']);
                        if (ArchitectConstant::ARCHITECT_SALE == $data['role_type'] || $data['is_owner']) {
                            $info[] = $data;
                        }
                    }
                }

                foreach ($info as $key => $data) {
                    if ((ArchitectConstant::ARCHITECT_SALE == $data['role_type'] && ArchitectConstant::TEAM_LEVEL_LEADER == $data['level']) || $data['is_owner']) {
                        $roleList[] = $data;
                    }
                }
            }

            usort($roleList, [$this, 'sortRoleCmp']);
        }
        return $roleList;
    }

    public function getArchData($teamId, $year, $quarter, $archType, $channelType = 'direct')
    {
        $day = Carbon::create($year, $quarter * 3, 1);
        $begin = $day->firstOfQuarter()->format('Y-m-d');
        $end = $day->lastOfQuarter()->format('Y-m-d');
        $params = [
            'team_id' => $teamId,
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        $ret = $this->architectClient->getTeamSub($params);
        $data = $ret['data'];
        $teamIds = [];
        foreach ($data as &$row) {
            if (array_key_exists('team_id', $row)) {
                $teamIds[] = $row['team_id'];
                continue;
            }
            if (array_key_exists('sale_id', $row)) {
                //是销售数据，增加上team_id
                $row['team_id'] = $teamId;
                continue;
            }
        }
        if (empty($teamIds)) {
            //销售数据，直接返回
            Log::debug("sales: " . json_encode($data));
            return $data;
        }
        //小组数据，需要获取owner
        //需要获取owner
        $params = [
            'team_id' => implode(',', $teamIds),
            'include' => 'owner',
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        $ret = $this->architectClient->getTeamList($params);
        $data = $ret['data'];
        Log::debug("get with owner: " . json_encode($data));
        return $this->filterArchData($year, $quarter, $data, $archType, $channelType);
    }

    protected function filterArchData($year, $quarter, $data, $archType, $channelType = 'direct')
    {
        switch ($archType) {
            case RevenueConst::ARCH_TYPE_NATION:
                return $this->filterDepartments($year, $quarter, $data, $channelType);
            default:
                return $data;
        }
    }

    protected function filterDepartments($year, $quarter, $data, $channelType = 'direct')
    {
        /**
         * @var $revenueService RevenueService
         */
        $revenueService = app(RevenueService::class);
        $validDepartments = [];

        if ($revenueService->getNewArchitectInfo($year, $quarter)) {
            $departmentsConfig = RevenueConst::$newDepartments;
        } else {
            $departmentsConfig = RevenueConst::$departments;
        }
        if ($channelType == null) {
            $deptIds = [];
            foreach ($departmentsConfig as $channel => $departmentIds) {
                $deptIds = array_merge($deptIds, $departmentIds);
            }
        } else {
            $deptIds = $departmentsConfig[$channelType];
        }

        foreach ($data as $row) {
            if (in_array($row['team_id'], $deptIds)) {
                $validDepartments[] = $row;
            }
        }
        $user = Auth::user();
        if ($user->getRtx() != 'kikifan') {
            return $validDepartments;
        }
        $ret = [];
        foreach ($validDepartments as $row) {
            $owners = $row['owner'];
            $owner = isset($owners[0]) ? $owners[0] : [];
            if (isset($owner['rtx']) && $owner['rtx'] == $user->getRtx()) {
                $ret[] = $row;
            }
        }
        return $ret;
    }

    /**
     * 获取用户在给定时间内，可管理的最高层级
     * @param User $user
     * @param $year
     * @param $quarter
     * @return array
     */
    public function getUserHighestArch(User $user, $year, $quarter)
    {
        $day = Carbon::create($year, $quarter * 3, 1);
        $begin = $day->firstOfQuarter()->format('Y-m-d');
        $end = $day->lastOfQuarter()->format('Y-m-d');
        if ($user->isAdmin() || $user->isOperator()) {
            //获取部门
            $params = [
                'level' => ArchitectConstant::TEAM_LEVEL_DEPT,
                'include' => 'owner',
                'begin_date' => $begin,
                'end_date' => $end,
            ];
            $ret = $this->architectClient->getTeamList($params);
            $data = $ret['data'];
            $data = $this->filterDepartments($year, $quarter, $data, null);
            return $data;
        }
        $saleId = $user->getSaleId();
        $params = [
            'sale_id' => $saleId,
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        $ret = $this->architectClient->getSaleTeams($params);
        $rows = $ret['data'];
        $minLevel = ArchitectConstant::TEAM_LEVEL_LEADER;
        foreach ($rows as $row) {
            if ($row['is_hidden']) {
                continue;
            }
            if ($row['level'] < $minLevel) {
                $minLevel = $row['level'];
            }
        }
        $data = [];
        foreach ($rows as $row) {
            if ($row['level'] == $minLevel && $row['is_owner'] == 1) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getClients($clientIds)
    {
        $selects = [
            'c.Fclient_id as client_id',
            'c.Fclient_name as client_name',
            's.Fshort_id as short_id',
            's.Fshort_name as short_name',
        ];
        /**
         * @var Connection $connection
         */
        $connection = DB::connection('crm_kernal');
        $cond = [
            'c.Fis_del' => 0,
            'csc.Fis_del' => 0,
            'sm.Fis_del' => 0,
            's.Fis_del' => 0,
            'csc.Fsale_channel' => 1,
        ];
        $clients = $connection->table('t_client as c')
            ->join('t_client_sale_channel as csc', 'c.Fclient_id', '=', 'csc.Fclient_id')
            ->join('t_short_map as sm', 'sm.Fclient_id', '=', 'c.Fclient_id')
            ->join('t_short as s', 's.Fshort_id', '=', 'sm.Fshort_id')
            ->whereIn('c.Fclient_id', $clientIds)
            ->where($cond)
            ->select($selects)
            ->get();
        $data = [];
        foreach ($clients as $client) {
            $data[] = (array)$client;
        }
        return $data;
    }

    public function getAgents($agentIds)
    {
        $selects = [
            'a.agent_id as agent_id',
            'a.agent_name as agent_name',
            's.short_id as short_id',
            's.short_name as short_name',
        ];
        /**
         * @var Connection $connection
         */
        $connection = DB::connection('crm_kernal');
        $cond = [
            'a.is_del' => 0,
            'asc.is_del' => 0,
            'sm.is_del' => 0,
            's.is_del' => 0,
            'asc.sale_channel' => 1,
        ];
        $agents = $connection->table('t_agent as a')
            ->join('t_agent_sale_channel as asc', 'a.agent_id', '=', 'asc.agent_id')
            ->join('t_agent_short_map as sm', 'sm.agent_id', '=', 'a.agent_id')
            ->join('t_agent_short as s', 's.short_id', '=', 'sm.short_id')
            ->whereIn('a.agent_id', $agentIds)
            ->where($cond)
            ->select($selects)
            ->get();
        $data = [];
        foreach ($agents as $agent) {
            $data[] = (array)$agent;
        }
        return $data;
    }
}

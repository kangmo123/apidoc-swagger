<?php

namespace App\Services;

use App\Constant\ArchitectConstant;
use App\Constant\TaskConstant;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\Services\Common\ConfigService;
use App\Utils\Utils;

/**
 * Class ArchitectService
 * @package App\Services\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class BaseArchitectService
{
    const TYPE_DIRECT = 1;
    const TYPE_CHANNEL = 2;

    const NODE_TYPE_SALE = 1; //组织架构微服务接口中node_type=1是销售，0是小组
    const NODE_TYPE_TEAM = 0;

    const TEAM_LEVEL_SYSTEM = 0;
    const TEAM_LEVEL_DEPARTMENT = 1;
    const TEAM_LEVEL_AREA = 10;
    const TEAM_LEVEL_DIRECTOR = 20;
    const TEAM_LEVEL_LEADER = 30;
    const TEAM_LEVEL_SALE = 40;

    /**
     * 组织架构树-销售
     */
    const ARCHI_TREE_TYPE_SALE = 1;
    /**
     * 组织架构树-销售+渠道
     */
    const ARCHI_TREE_TYPE_ALL = 2;

    public static $groups = [
        self::TYPE_DIRECT => ArchitectConstant::GROUP_DIRECT,
        self::TYPE_CHANNEL => ArchitectConstant::GROUP_CHANNEL,
    ];

    /**
     * 直客、ka渠道、渠道业务的任务查询页面看到的组
     *
     * @var array
     */
    public static $operatorGroupTeamMap = [
        self::TYPE_DIRECT => ArchitectConstant::DIRECT_SALE_TEAM_GUID,
        self::TYPE_CHANNEL => ArchitectConstant::DIRECT_SALE_TEAM_GUID,
    ];

    /**
     * @var ConfigService
     */
    protected $configService = null;

    /**
     * @var ArchitectClient
     */
    protected $architectClient = null;

    protected $operatorGroupPrivilege = null;

    public function __construct(ConfigService $configService, ArchitectClient $architectClient)
    {
        $this->configService = $configService;
        $this->architectClient = $architectClient;
    }

    /**
     * @param $group
     * @return mixed
     */
    public static function getUserGroupValue($group)
    {
        $groupMap = [];
        foreach (TaskConstant::$groups as $k => $v) {
            $groupMap[$v['group']] = $k;
        }
        return isset($groupMap[$group]) ? $groupMap[$group] : null;
    }

    public function getTeamInfo($teamId, $period)
    {
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $params = [
            'team_id' => $teamId,
            'begin_date' => $begin,
            'end_date' => $end,
        ];
        $ret = $this->architectClient->getTeam($params);
        $teams = $ret['data'];
        return $teams[0] ?? [];
    }

    /**
     * 用于给角色进行排序
     * 排序规则：先看级别，级别越高排在越前；再看时间，时间越晚排在越前（即最新的排在前面）
     * @author: meyeryan@tencent.com
     *
     * @param $roleA
     * @param $roleB
     * @return int
     */
    protected function sortRoleCmp($roleA, $roleB)
    {
        //级别越高排在越前，roleType越小级别越高
        if ($roleA['role_type'] < $roleB['role_type']) {
            return -1;
        }
        if ($roleA['role_type'] > $roleB['role_type']) {
            return 1;
        }

        return 0;
    }

    /**
     * 获取sale在时间范围里面所属的组织架构
     * @param $sale
     * @param $begin
     * @param $end
     * @param $minLevel
     * @param null $isOwner
     * @return array
     */
    public function getSaleTeams($sale, $begin, $end, $minLevel, $isOwner = null)
    {
        $params = [
            'sale_id' => $sale,
            'begin_date' => $begin,
            'end_date' => $end,
            'isOwner' => $isOwner,
        ];
        $ret = $this->architectClient->getSaleTeams($params);
        $teams = $ret['data'];
        $data = [];
        foreach ($teams as $team) {
            if ($team['is_hidden']) {
                continue;
            }
            $level = $team['level'];
            if ($level < $minLevel) {
                continue;
            }
            $isSaleOwner = $team['is_owner'];
            if ($level == User::ROLE_LEADER && !$isSaleOwner) {
                $team['level'] = User::ROLE_SALE;
            }
            $data[] = $team;
        }
        return $data;
    }

    /**
     * 获取组织架构树
     *
     * @param null $begin
     * @param null $end
     * @param null $teamId
     * @param string $type
     * @return array
     */
    public function getArchitectTree($begin = null, $end = null, $teamId = null, $type = "1,2")
    {
        $params = [];
        if (!empty($begin)) {
            $params["begin_date"] = $begin;
        }
        if (!empty($end)) {
            $params["end_date"] = $end;
        }
        if (!empty($type)) {
            $params["type"] = $type;
        }
        if (!empty($teamId)) {
            $params["team_id"] = $teamId;
        }
        $ret = $this->architectClient->getTeamSaleTree($params);
        return isset($ret["data"]) ? $ret["data"] : [];
    }
}

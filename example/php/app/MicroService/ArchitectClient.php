<?php

namespace App\MicroService;

use App\Exceptions\API\NotFound;

/**
 * Class ArchitectClient
 * @package App\MicroService\Architect
 * @author caseycheng <caseycheng@tencent.com>
 *
 * @method array getTeamSaleTree($params)
 * @method array getSale($params)
 * @method array getOwner($params)
 * @method array getTeamSub($params)
 * @method array getTeamList($params)
 * @method array getSaleList($params)
 * @method array getSaleTeams($params)
 * @method array getTeam($params)
 * @method array getSaleAncestor($params)
 */
class ArchitectClient extends Client
{
    protected $methods = [
        "getTeamSaleTree" => [
            "method" => "get",
            "uri" => "/v1/team-sales/tree",
        ],
        "getSale" => [
            "method" => "get",
            "uri" => "/v1/sales/{sale_id}",
            "replacement" => true,
        ],
        "getOwner" => [
            "method" => "get",
            "uri" => "/v1/teams/{team_id}/owner",
            "replacement" => true,
        ],
        "getTeam" => [
            "method" => "get",
            "uri" => "/v1/teams/{team_id}",
            "replacement" => true,
        ],
        "getTeamSub" => [
            "method" => "get",
            "uri" => "/v1/teams/{team_id}/subordinates",
            "replacement" => true,
        ],
        "getTeamList" => [
            "method" => "get",
            "uri" => "/v1/teams",
        ],
        "getSaleList" => [
            "method" => "get",
            "uri" => "/v1/sales",
        ],
        "getSaleTeams" => [
            "method" => "get",
            "uri" => "/v1/sales/{sale_id}/teams",
            "replacement" => true,
        ],
        "getHierarchy" => [
            "method" => "get",
            "uri" => "/v1/teams/{team_id}/hierarchy",
            "replacement" => true,
        ],
        "getSaleAncestor" => [
            "method" => "get",
            "uri" => "/v1/sales/{sale_id}/ancestors",
            "replacement" => true,
        ]
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "archi.service";
    }

    /**
     * 获取销售信息
     * @param $saleId
     * @param null $include
     * @return array
     */
    public function getSaleInfo($saleId, $include = null)
    {
        try {
            $ret = $this->getSale(["sale_id" => $saleId, "include" => $include]);
        } catch (NotFound $e) {
            throw new NotFound("{$saleId}在组织架构中不存在");
        }
        return $ret;
    }

    /**
     * 获取小组负责人
     *
     * @param $teamId
     * @param $begin
     * @param $end
     * @return mixed
     */
    public function getTeamOwner($teamId, $begin, $end)
    {
        $params = [
            "team_id" => $teamId
        ];
        if (!empty($begin)) {
            $params["begin_date"] = $begin;
        }
        if (!empty($end)) {
            $params["end_date"] = $end;
        }
        $ret = $this->getOwner($params);
        return !empty($ret) ? current($ret) : [];
    }

    /**
     * 获取小组信息
     *
     * @param $teamId
     * @param $begin
     * @param $end
     * @return array|mixed
     */
    public function getTeamInfo($teamId, $begin, $end)
    {
        $ret = $this->getTeam(["team_id" => $teamId, "begin_date" => $begin, "end_date" => $end]);
        return !empty($ret) ? current($ret) : [];
    }
}

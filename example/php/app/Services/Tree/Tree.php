<?php

namespace App\Services\Tree;

use App\Constant\ArchitectConstant;

class Tree implements \JsonSerializable
{
    /**
     * @var TeamNode
     */
    protected $root = null;

    protected $teamNodeMap = [];

    public function __construct()
    {
        $this->root = new TeamNode();
        $this->root->setLevel(ArchitectConstant::TEAM_LEVEL_NONE);
    }

    /**
     * @return TeamNode
     */
    public function getRoot(): TeamNode
    {
        return $this->root;
    }

    /**
     * @param TeamNode $root
     */
    public function setRoot(TeamNode $root): void
    {
        $this->root = $root;
    }

    public function build($architects)
    {
        $team = $this->buildTeamNode($architects);
        if (!empty($team)) {
            $this->root->addChild($team);
        }
    }

    public function addChild(TeamNode $teamNode)
    {
        $this->root->addChild($teamNode);
    }

    public function createTeamNode($teamInfo)
    {
        $team = new TeamNode();
        $team->setId($teamInfo['id']);
        $team->setTeamId($teamInfo['team_id']);
        $team->setName($teamInfo['name']);
        $team->setCode($teamInfo['code']);
        $team->setLevel($teamInfo['level']);
        $team->setPid($teamInfo['pid']);
        $team->setType($teamInfo['type']);
        $team->setBegin($teamInfo['begin']);
        $team->setEnd($teamInfo['end']);
        $team->setSort($teamInfo['sort']);
        return $team;
    }

    /**
     * @param $teamInfo
     * @return TeamNode
     */
    protected function buildTeamNode($teamInfo)
    {
        if (empty($teamInfo) || $teamInfo['node_type'] == ArchitectConstant::NODE_TYPE_SALE) {
            return;
        }
        $team = $this->createTeamNode($teamInfo);
        //set team node map
        $this->teamNodeMap[$team->getTeamId()] = $team;
        //build team children
        $this->buildTeamChildren($team, $teamInfo['children']);
        return $team;
    }

    /**
     * @param TeamNode $teamNode
     * @param $children
     */
    protected function buildTeamChildren($teamNode, $children)
    {
        foreach ($children as $child) {
            if ($child['node_type'] == ArchitectConstant::NODE_TYPE_SALE) {
                //这个是销售
                $saleNode = $this->buildSaleNode($child);
                $saleNode->setParent($teamNode);
                if ($saleNode->getIsOwner()) {
                    $teamNode->setLeader($saleNode);
                } else {
                    $teamNode->addSale($saleNode);
                }
            } else {
                //是小组
                $subTeam = $this->buildTeamNode($child);
                $teamNode->addChild($subTeam);
                $subTeam->setParent($teamNode);
            }
        }
    }

    protected function buildSaleNode($saleInfo)
    {
        $sale = new SaleNode();
        $sale->setId($saleInfo['id']);
        $sale->setSaleId($saleInfo['sale_id']);
        $sale->setRtx($saleInfo['rtx']);
        $sale->setName($saleInfo['name']);
        $sale->setFullname($saleInfo['fullname']);
        $sale->setMobile($saleInfo['mobile']);
        $sale->setEmail($saleInfo['email']);
        $sale->setEnable($saleInfo['enable']);
        $sale->setHireDate($saleInfo['hire_date']);
        $sale->setLeaveDate($saleInfo['leave_date']);
        $sale->setDeleteDate($saleInfo['delete_date']);
        $sale->setTofStatus($saleInfo['tof_status']);
        $sale->setIsOwner($saleInfo['is_owner']);
        $sale->setPid($saleInfo['pid']);
        $sale->setDate($saleInfo['date']);
        return $sale;
    }

    /**
     * 通过广度优先遍历，获取所有的team_id和sale_id
     * @param $teamList
     * @param bool $split
     * @param bool $withLeaderId
     * @return array
     */
    public function getTeamAndSaleIds($teamList, $split = false, $withLeaderId = false)
    {
        $teamId = $saleId = [];
        foreach ($teamList as $team) {
            $teamId[$team->getTeamId()] = $team->getTeamId();
            if ($withLeaderId && $team->getLeader()) {
                $saleId[$team->getLeader()->getSaleId()] = $team->getLeader()->getSaleId();
            }
            $children = $team->getChildren();
            $sales = $team->getSales();
            if ($children->isNotEmpty() || $sales->isEmpty()) {
                continue;
            }
            foreach ($sales as $sale) {
                $saleId[$sale->getSaleId()] = $sale->getSaleId();
            }
        }
        if ($split) {
            $data = [
                'team_id' => $teamId,
                'sale_id' => $saleId,
            ];
        } else {
            $data = array_merge($teamId, $saleId);
            $data = array_values($data);
        }
        return $data;
    }

    /**
     * @param TeamNode $teamNode
     * @param bool $withRoot
     * @return array|TeamNode[]
     */
    public function bfs(TeamNode $teamNode = null, $withRoot = false)
    {
        if (empty($teamNode)) {
            $queue = clone $this->root->getChildren();
        } else {
            $queue = clone $teamNode->getChildren();
        }
        $data = collect([]);
        while ($queue->isNotEmpty()) {
            $team = $queue->pop();
            $data->push($team);
            $children = $team->getChildren();
            foreach ($children as $child) {
                $queue->prepend($child);
            }
        }
        if ($withRoot) {
            $data->prepend($teamNode);
        }
        return $data->all();
    }

    public function jsonSerialize()
    {
        $data = $this->root->jsonSerialize();
        return $data['children']->all();
    }
}

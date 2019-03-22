<?php

namespace App\Services\Task;

use App\Constant\ArchitectConstant;
use App\Constant\TaskConstant;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Common\ConfigService;
use App\Services\Tree\SaleNode;
use App\Services\Tree\TeamNode;
use App\Services\Tree\Tree;
use Carbon\Carbon;

/**
 * Class StepService
 * @package App\Services\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class StepService
{

    const STEP_WAITING = "waiting";
    const STEP_TOTAL_TASK_CONFIRMING = "total_task_confirming";
    const STEP_OPERATOR_ASSIGNING = "operator_assigning";
    const STEP_DIRECTOR_ASSIGNING = "director_assigning";
    const STEP_LEADER_ASSIGNING = "leader_assigning";
    const STEP_DIRECTOR_CONFIRMING = "director_confirming";
    const STEP_OPERATOR_CONFIRMING = "operator_confirming";
    const STEP_FINISHED = "finished";

    const INDEX_WAITING = 1;
    const INDEX_TOTAL_TASK_CONFIRMING = 2;
    const INDEX_OPERATOR_ASSIGNING = 3;
    const INDEX_DIRECTOR_ASSIGNING = 4;
    const INDEX_LEADER_ASSIGNING = 5;
    const INDEX_DIRECTOR_CONFIRMING = 6;
    const INDEX_OPERATOR_CONFIRMING = 7;
    const INDEX_FINISHED = 8;

    static $steps = [
        self::INDEX_WAITING => self::STEP_WAITING,
        self::INDEX_TOTAL_TASK_CONFIRMING => self::STEP_TOTAL_TASK_CONFIRMING,
        self::INDEX_OPERATOR_ASSIGNING => self::STEP_OPERATOR_ASSIGNING,
        self::INDEX_DIRECTOR_ASSIGNING => self::STEP_DIRECTOR_ASSIGNING,
        self::INDEX_LEADER_ASSIGNING => self::STEP_LEADER_ASSIGNING,
        self::INDEX_DIRECTOR_CONFIRMING => self::STEP_DIRECTOR_CONFIRMING,
        self::INDEX_OPERATOR_CONFIRMING => self::STEP_OPERATOR_CONFIRMING,
        self::INDEX_FINISHED => self::STEP_FINISHED,
    ];

    /**
     * @var TaskClient
     */
    protected $client = null;

    public function __construct(TaskClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param User $user
     * @param $period
     * @param $teamId
     * @param $tree
     * @return int
     */
    public function getCurrentStep(User $user, $period, $teamId, $tree)
    {
        if (!$this->canAssignTaskForPeriod($period)) {
            return self::INDEX_WAITING;
        }
        if ($user->isAdmin() || $user->isOperator()) {
            return $this->getOperatorStep($user, $teamId, $tree);
        }
        return $this->getSaleStep($user, $teamId, $tree);
    }

    /**
     * @param $period
     * @return bool
     */
    protected function canAssignTaskForPeriod($period)
    {
        $today = Carbon::create()->startOfDay();
        list($year, $quarter) = explode("Q", $period);
        $day = Carbon::create($year, 3 * $quarter)->firstOfQuarter();
        if ($day < $today) {
            return true;
        }
        /**
         * @var ConfigService $configService
         */
        $configService = app()->make(ConfigService::class);
        $date = $configService->getTaskBeginDate();
        return $today >= $date;
    }

    /**
     * @param User $user
     * @param $teamId
     * @param Tree $tree
     * @return int
     */
    protected function getOperatorStep(User $user, $teamId, $tree)
    {
        $data = $tree->getRoot()->getExtraData();
        if (empty($data['task'])) {
            //树结构的root节点上的task为空，代表没有制定总任务
            return self::INDEX_TOTAL_TASK_CONFIRMING;
        }
        $task = $data['task'];
        if ($task['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            //总任务已经锁定，即锁定了全部总监的任务，结束
            return self::INDEX_FINISHED;
        }
        $teamList = $tree->bfs();
        $centers = $teams = [];
        foreach ($teamList as $teamNode) {
            switch ($teamNode->getLevel()) {
                case ArchitectConstant::TEAM_LEVEL_DIRECTOR:
                    $centers[] = $teamNode;
                    break;
                case ArchitectConstant::TEAM_LEVEL_LEADER:
                    $teams[] = $teamNode;
                default:
            }
        }
        //判断总监任务
        $centerLocked = true;
        foreach ($centers as $center) {
            if (app()->environment() != 'production' &&
                !in_array($center->getName(), TaskConstant::$testCentersForTask)) {
                //TODO: delete
                continue;
            }
            $data = $center->getExtraData();
            if (empty($data['task'])) {
                //总监任务为空，给总监分任务
                return self::INDEX_OPERATOR_ASSIGNING;
            }
            $task = $data['task'];
            if ($task['status'] != TaskConstant::TASK_STATUS_LOCKED) {
                $centerLocked = false;
                break;
            }
        }
        if ($centerLocked) {
            //总监的任务都锁定了
            return self::INDEX_OPERATOR_CONFIRMING;
        }
        //总监任务没锁定，判断小组任务是否都分配了
        $teamTaskAssign = $saleTaskAssign = true;
        foreach ($teams as $team) {
            $data = $team->getExtraData();
            if (empty($data['task'])) {
                //小组任务为空
                $teamTaskAssign = false;
                break;
            }
            $sales = $team->getSales();
            foreach ($sales as $sale) {
                $data = $sale->getExtraData();
                if (empty($data['task'])) {
                    //小组任务为空
                    $saleTaskAssign = false;
                    break;
                }
            }
        }
        if (!$teamTaskAssign) {
            //小组任务没有制定
            return self::INDEX_DIRECTOR_ASSIGNING;
        }
        if (!$saleTaskAssign) {
            //有销售任务没有制定
            return self::INDEX_LEADER_ASSIGNING;
        }
        //小组和销售的任务都制定了，有总监没有锁定，等待总监锁定任务
        return self::INDEX_DIRECTOR_CONFIRMING;
    }

    /**
     * @param User $user
     * @param $teamId
     * @param Tree $tree
     * @return int
     */
    protected function getSaleStep(User $user, $teamId, $tree)
    {
        $data = $tree->getRoot()->getExtraData();
        if (empty($data['task'])) {
            //树结构的root节点上的task为空，代表没有制定总任务
            return self::INDEX_TOTAL_TASK_CONFIRMING;
        }
        $task = $data['task'];
        if ($task['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            //总任务已经锁定，即锁定了全部总监的任务，结束
            return self::INDEX_FINISHED;
        }
        $teamList = $tree->bfs();
        $teamName = "";
        foreach ($teamList as $teamNode) {
            if ($teamNode->getLevel() < ArchitectConstant::TEAM_LEVEL_DIRECTOR) {
                continue;
            }
            if ($teamNode->getTeamId() != $teamId) {
                continue;
            }
            if ($user->getSaleId() == $teamNode->getLeader()->getSaleId()) {
                //是所选team的leader
                return $this->judgeForLeader($teamNode, $user);
            }
            $sales = $teamNode->getSales();
            foreach ($sales as $sale) {
                if ($user->getSaleId() == $sale->getSaleId()) {
                    return $this->judgeForSale($teamNode, $sale);
                }
            }
            $teamName = $teamNode->getName();
        }
        $team = empty($teamName) ? $teamId : "{$teamName}<{$teamId}>";
        $msg = "{$user->getRtx()}不在{$team}中";
        throw new \RuntimeException($msg);
    }

    protected function judgeForLeader(TeamNode $team, User $user)
    {
        if ($team->getChildren()->isEmpty()) {
            return $this->judgeForLowerTeam($team, $user);
        }
        return $this->judgeForUpperTeam($team, $user);
    }

    protected function judgeForUpperTeam(TeamNode $team, User $user)
    {
        //判断总监任务
        $data = $team->getExtraData();
        if (empty($data['task'])) {
            //总监任务为空，给总监分任务
            return self::INDEX_OPERATOR_ASSIGNING;
        }
        $task = $data['task'];
        if ($task['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            //总监任务已经锁定，等待运营锁定
            return self::INDEX_OPERATOR_CONFIRMING;
        }
        //总监任务没有锁定，判断小组任务是否都分配了
        $children = $team->getChildren();
        $teamTaskAssign = $saleTaskAssign = true;
        foreach ($children as $child) {
            $data = $child->getExtraData();
            if (empty($data['task'])) {
                //小组任务为空
                $teamTaskAssign = false;
                break;
            }
            $sales = $child->getSales();
            foreach ($sales as $sale) {
                $data = $sale->getExtraData();
                if (empty($data['task'])) {
                    //小组任务为空
                    $saleTaskAssign = false;
                    break;
                }
            }
        }
        if (!$teamTaskAssign) {
            //小组任务没有制定
            return self::INDEX_DIRECTOR_ASSIGNING;
        }
        if (!$saleTaskAssign) {
            //有销售任务没有制定
            return self::INDEX_LEADER_ASSIGNING;
        }
        //小组和销售的任务都制定了，有总监没有锁定，等待总监锁定任务
        return self::INDEX_DIRECTOR_CONFIRMING;
    }

    protected function judgeForLowerTeam(TeamNode $team, User $user)
    {
        //判断小组任务
        $data = $team->getExtraData();
        if (empty($data['task'])) {
            //小组任务为空，判断总监的任务
            $parent = $team->getParent();
            $data = $parent->getExtraData();
            if (empty($data['task'])) {
                //总监任务为空
                return self::INDEX_OPERATOR_ASSIGNING;
            }
            //等待总监制定
            return self::INDEX_DIRECTOR_ASSIGNING;
        }
        //小组任务不为空，再判断组员的任务都分配了吗
        $saleTaskAssign = true;
        $sales = $team->getSales();
        foreach ($sales as $sale) {
            $data = $sale->getExtraData();
            if (empty($data['task'])) {
                //小组任务为空
                $saleTaskAssign = false;
                break;
            }
        }
        if (!$saleTaskAssign) {
            //组员任务没分
            return self::INDEX_LEADER_ASSIGNING;
        }
        //组员任务分配了，看一下锁定的状态
        $task = $data['task'];
        if ($task['status'] != TaskConstant::TASK_STATUS_LOCKED) {
            //小组没有锁定，等待总监锁
            return self::INDEX_DIRECTOR_CONFIRMING;
        }
        //小组锁定了，总监肯定就锁定了，直接看总任务是否锁定，但是进到这里的话，总任务肯定没有锁，所以是运营锁定中
        return self::INDEX_OPERATOR_CONFIRMING;
    }

    protected function judgeForSale(TeamNode $team, SaleNode $sale)
    {
        //判断小组任务
        $data = $team->getExtraData();
        if (empty($data['task'])) {
            //小组任务为空，判断总监的任务
            $parent = $team->getParent();
            $data = $parent->getExtraData();
            if (empty($data['task'])) {
                //总监任务为空
                return self::INDEX_OPERATOR_ASSIGNING;
            }
            //等待总监制定
            return self::INDEX_DIRECTOR_ASSIGNING;
        }
        $task = $data['task'];
        if ($task['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            //小组和总监任务是同时由总监锁定的，等待运营锁定
            return self::INDEX_OPERATOR_CONFIRMING;
        }
        //小组任务没有锁定，判断个人任务
        $data = $sale->getExtraData();
        if (empty($data['task'])) {
            //个人任务未制定
            return self::INDEX_LEADER_ASSIGNING;
        }
        return self::INDEX_DIRECTOR_CONFIRMING;
    }

    protected function isTaskLocked($task)
    {
        return $task['status'] == TaskConstant::TASK_STATUS_LOCKED;
    }
}

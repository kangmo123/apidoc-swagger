<?php

namespace App\Services\Task;

use App\Constant\ArchitectConstant;
use App\Constant\TaskConstant;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Common\ConfigService;
use App\Services\Tree\ITreeNode;
use App\Services\Tree\SaleNode;
use App\Services\Tree\TeamNode;
use App\Services\Tree\Tree;
use App\Utils\Utils;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QueryService
{

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ArchitectService
     */
    protected $architectService;

    /**
     * @var TaskService $taskService
     */
    protected $taskService;

    /**
     * @var TaskClient
     */
    protected $taskClient;

    public function __construct(
        ConfigService $configService,
        ArchitectService $architectService,
        TaskService $taskService,
        TaskClient $taskClient
    ) {
        $this->configService = $configService;
        $this->architectService = $architectService;
        $this->taskService = $taskService;
        $this->taskClient = $taskClient;
    }

    /**
     * @param User $user
     * @param $period
     * @param $teamId
     * @param bool $showForSale - 是否过滤组织架构任务树，只显示Sale能看到的那部分
     * @return Tree|array
     */
    public function query(User $user, $period, $teamId, $showForSale = true)
    {
        if ($user->isOperator() || $user->isAdmin()) {
            return $this->queryTaskTreeForOperator($user, $period, $teamId);
        }
        return $this->queryTaskTreeForSale($user, $period, $showForSale);
    }

    /**
     * @param User $user
     * @param $period
     * @param bool $showForSale
     * @return Tree
     */
    protected function queryTaskTreeForSale(User $user, $period, $showForSale = true)
    {
        //根据销售所属的全部team，找到其对应的部门
        $ancestors = $this->getSaleAncestors($user, $period);
        $department = $this->getSaleDepartment($ancestors);
        if (empty($department)) {
            throw new \RuntimeException($user->getRtx() . " has no department at {$period}");
        }
        $dimensions = $this->getSaleTaskDimension($department['team_id']);

        //获取销售所属部门的组织架构任务树
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $architects = $this->architectService->getArchitectTree($begin, $end, $department['team_id']);
        $tree = new Tree();
        $tree->getRoot()->setTeamId($department['team_id']);
        foreach ($architects as $architect) {
            $tree->build($architect);
        }

        $tasks = $this->getTaskForTree($tree, $period);
        $this->attachTaskDetailsToArchitect($tree, $tasks, $dimensions);
        if ($showForSale) {
            //根据销售所属的team，过滤出销售能看的组织架构
            $tree = $this->filterTaskTreeForSale($user, $period, $tree, $ancestors, $dimensions);
        }
        return $tree;
    }

    /**
     * @param User $user
     * @param $period
     * @param $teamId
     * @return array|Tree
     */
    protected function queryTaskTreeForOperator(User $user, $period, $teamId)
    {
        $dimensions = $this->taskService->getTaskDimension($teamId);
        $architects = $this->getArchitectsByGroup($period, $teamId);

        $tree = new Tree();
        $tree->getRoot()->setTeamId($teamId);
        $tree->build($architects);
        $tasks = $this->getTaskForTree($tree, $period);
        $this->attachTaskDetailsToArchitect($tree, $tasks, $dimensions);
        return $tree;
    }

    /**
     * @param Tree $tree
     * @param $period
     * @return mixed
     */
    protected function getTaskForTree($tree, $period)
    {
        $teamList = $tree->bfs();
        $targetIds = $tree->getTeamAndSaleIds($teamList);
        //也去查询Tree的Root节点的任务数据，这里代表的是该tree管理范围的总任务
        $targetIds = array_prepend($targetIds, $tree->getRoot()->getTeamId());
        //获取任务
        $tasks = $this->taskService->queryTask($period, $targetIds);
        return $tasks;
    }

    /**
     * 根据销售，和其对应的组织架构任务树，过滤出销售能看的组织架构任务树
     * @param User $user
     * @param $period
     * @param $tree
     * @param $ancestors
     * @param $dimensions
     * @return Tree
     */
    protected function filterTaskTreeForSale(User $user, $period, $tree, $ancestors, $dimensions)
    {
        $ancestorTeamIds = [];
        foreach ($ancestors as $ancestor) {
            $ancestorTeamIds[] = $ancestor['team_id'];
        }
        $teamList = $tree->bfs();
        foreach ($teamList as $team) {
            if (!$team->isMarked() && in_array($team->getTeamId(), $ancestorTeamIds)) {
                //遍历组织架构树，如果team_id是当前销售所在的team的话，把这个team得下级全部标记上
                $team->mark();
            }
        }
        $newTree = new Tree();
        foreach ($teamList as $team) {
            //再将有标记的team，并且parent没有标记的team变成1个tree
            if (!$team->isMarked() || $team->getParent()->isMarked()) {
                continue;
            }
            $tmpTeam = $team;
            if ($team->getLeader()->getSaleId() != $user->getSaleId()) {
                //销售不是这个team的leader
                $tmpTeam = clone $team;
                $sales = $tmpTeam->getSales();
                $tmpSales = collect([]);
                foreach ($sales as $sale) {
                    if ($sale->getSaleId() == $user->getSaleId()) {
                        $tmpSales->push($sale);
                    }
                }
                $tmpTeam->setSales($tmpSales);
            }
            $newTree->addChild($tmpTeam);
        }

        $teamList = $newTree->bfs();
        $targetIds = $newTree->getTeamAndSaleIds($teamList);

        //获取任务
        $tasks = $this->taskService->queryTask($period, $targetIds);
        $this->attachTaskDetailsToArchitect($newTree, $tasks, $dimensions);
        return $newTree;
    }

    protected function getSaleAncestors($user, $period)
    {
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $ancestors = $this->architectService->getSaleAncestors($user->getSaleId(), $begin, $end);
        return $ancestors;
    }

    /**
     * 获取销售所属的部门
     * @param $ancestors
     * @return array
     */
    protected function getSaleDepartment($ancestors)
    {
        //找当前销售所属的部门
        $department = [];
        foreach ($ancestors as $teamId => $teams) {
            if (empty($teams['parents'])) {
                continue;
            }
            //找parents中level = 1的数据
            $parents = $teams['parents'];
            foreach ($parents as $parent) {
                if ($parent['level'] == ArchitectConstant::TEAM_LEVEL_DEPT) {
                    $department = $parent;
                    break;
                }
            }
        }
        return Utils::removePrefixF($department);
    }

    /**
     * 根据销售，所处的部门，去t_config表找到对应的任务维度配置
     * @param $departmentId
     * @return array
     */
    protected function getSaleTaskDimension($departmentId)
    {
        //根据配置的部门channel，找到这个人看的任务维度配置
        $dimensions = $this->taskService->getTaskDimension($departmentId);
        return $dimensions;
    }

    /**
     * @param Tree $tree - 组织架构树
     * @param $tasks - 任务列表
     * @param $dimensions - 任务纬度，用于填充没有任务的组织
     */
    protected function attachTaskDetailsToArchitect($tree, $tasks, $dimensions)
    {
        $totalTask = $taskMap = [];
        foreach ($tasks as $task) {
            if ($task['type'] == TaskConstant::TYPE_TOTAL) {
                $totalTask = $task;
                continue;
            }
            $targetId = $task['target_id'];
            $parentId = $task['parent_id'];
            $hash = $targetId . "|" . $parentId;
            $taskMap[$hash] = $task;
        }
        $defaultDetails = [];
        foreach ($dimensions as $dimension) {
            $defaultDetails[$dimension['name']] = null;
        }
        //设置总任务数据到tree的root节点上
        $root = $tree->getRoot();
        $this->setTaskToTreeNode($root, $totalTask, $defaultDetails);

        $teamList = $tree->bfs();
        foreach ($teamList as $teamNode) {
            //设置小组的任务数据
            $hash = $teamNode->getTaskHash();
            $task = $taskMap[$hash] ?? [];
            $this->setTaskToTreeNode($teamNode, $task, $defaultDetails);

            //设置销售的任务数据
            $sales = $teamNode->getSales();
            foreach ($sales as $sale) {
                $hash = $sale->getTaskHash();
                $task = $taskMap[$hash] ?? [];
                $this->setTaskToTreeNode($sale, $task, $defaultDetails);
            }
        }
    }

    protected function setTaskToTreeNode(ITreeNode $node, $task, $defaultDetails)
    {
        $details = empty($task) ? $defaultDetails : $task['details'];
        $tmp = [];
        foreach ($details as $name => $money) {
            if (!array_key_exists($name, $defaultDetails)) {
                continue;
            }
            $name = TaskConstant::revertDetail($name);
            $tmp[$name] = $money;
        }
        if (!empty($task)) {
            $task['details'] = $tmp;
        }
        $node->setExtraData(['task' => $task, 'details' => ['details' => $tmp]]);
    }

    /**
     * 根据管理员or运营管辖的范围，获取出对应的组织架构树
     * @param $period
     * @param $teamId
     * @return array
     */
    protected function getArchitectsByGroup($period, $teamId)
    {
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $tree = $this->architectService->getArchitectTree($begin, $end, $teamId);
        $architects = [];
        if (!empty($tree)) {
            $architects = $tree[0];
        }
        return $architects;
    }

    /**
     * @param Tree $tree
     * @return array|TeamNode[]
     */
    public function getOperatorCenterTeamNodeList(Tree $tree)
    {
        $teamList = $tree->bfs();
        $data = [];
        foreach ($teamList as $teamNode) {
            if ($teamNode->getLevel() != ArchitectConstant::TEAM_LEVEL_DIRECTOR) {
                continue;
            }
            if (!$teamNode->getLeader()) {
                continue;
            }
            if (app()->environment() != 'production' &&
                !in_array($teamNode->getName(), TaskConstant::$testCentersForTask)) {
                //TODO: delete
                continue;
            }
            $data[] = $teamNode;
        }
        return $data;
    }

    /**
     * @param Tree[] $treeMap
     * @return array
     */
    public function formatTreeForExport($treeMap)
    {
        $data = [];
        foreach ($treeMap as $period => $tree) {
            $teamList = $tree->bfs();
            $periodData = $this->formatTeamNodeListForExport($period, $teamList);
            $data = array_merge($data, $periodData);
        }
        return $data;
    }

    /**
     * @param $period
     * @param $teamList
     * @param bool $withSales
     * @return array
     */
    public function formatTeamNodeListForExport($period, $teamList, $withSales = true)
    {
        $teamData = $saleData = [];
        foreach ($teamList as $teamNode) {
            $teamData[] = $this->formatTeamNodeForExport($period, $teamNode);
            if ($withSales && $teamNode->getChildren()->isEmpty()) {
                $sales = $teamNode->getSales();
                foreach ($sales as $saleNode) {
                    $saleData[] = $this->formatSaleNodeForExport($period, $saleNode);
                }
            }
        }
        return array_merge($teamData, $saleData);
    }

    /**
     * @param $period
     * @param TeamNode[] $teamList
     * @return array
     */
    public function formatTeamNodeListForList($period, $teamList)
    {
        $tasks = [];
        foreach ($teamList as $teamNode) {
            $tasks[] = $this->formatTeamNodeForList($period, $teamNode);
        }
        return $tasks;
    }

    /**
     * @param $period
     * @param SaleNode[] $saleList
     * @return array
     */
    public function formatSaleNodeListForExport($period, $saleList)
    {
        $tasks = [];
        foreach ($saleList as $saleNode) {
            $tasks[] = $this->formatSaleNodeForExport($period, $saleNode);
        }
        return $tasks;
    }

    /**
     * @param $period
     * @param SaleNode[] $saleList
     * @return array
     */
    public function formatSaleNodeListForList($period, $saleList)
    {
        $tasks = [];
        foreach ($saleList as $saleNode) {
            $tasks[] = $this->formatSaleNodeForList($period, $saleNode);
        }
        return $tasks;
    }

    protected function formatTeamNodeForList($period, TeamNode $teamNode)
    {
        $data = $teamNode->getExtraData();
        $task = $data['task'] ?? [];
        if (empty($task)) {
            $task = [
                'period' => $period,
                'target_id' => $teamNode->getTeamId(),
                'parent_id' => $teamNode->getPid(),
                'fullname' => $teamNode->getLeader()->getFullname(),
                'team_name' => $teamNode->getName(),
                'level' => TaskConstant::convertArchitectLevelToTaskLevel($teamNode->getLevel()),
                'channel' => $teamNode->getType(),
                'type' => TaskConstant::TYPE_ARCHITECT,
                'status' => TaskConstant::TASK_STATUS_UNASSIGNED,
                'details' => $data['details']['details'],
            ];
        } else {
            $task['fullname'] = $teamNode->getLeader()->getFullname();
            $task['team_name'] = $teamNode->getName();
        }
        return $task;
    }

    protected function formatTeamNodeForExport($period, TeamNode $teamNode)
    {
        $prefix = [
            'period' => $period,
            'fullname' => $teamNode->getLeader()->getFullname(),
            'team_name' => $teamNode->getName(),
            'parent_fullname' => $teamNode->getParent() ? $teamNode->getParent()->getLeader()->getFullname() : "",
            'target_id' => $teamNode->getTeamId(),
            'parent_id' => $teamNode->getPid(),
            'channel' => $teamNode->getType(),
            'level' => TaskConstant::convertArchitectLevelToTaskLevel($teamNode->getLevel()),
        ];
        $data = $teamNode->getExtraData();
        $details = $data['details']['details'];
        foreach ($details as $name => &$money) {
            $money = TaskConstant::convertMoney($money);
        }
        unset($money);
        return array_merge($prefix, $details);
    }

    protected function formatSaleNodeForList($period, SaleNode $saleNode)
    {
        $data = $saleNode->getExtraData();
        $task = $data['task'] ?? [];
        if (empty($task)) {
            $task = [
                'period' => $period,
                'target_id' => $saleNode->getSaleId(),
                'parent_id' => $saleNode->getPid(),
                'fullname' => $saleNode->getFullname(),
                'team_name' => $saleNode->getParent()->getName(),
                'level' => TaskConstant::LEVEL_SALE,
                'channel' => $saleNode->getParent()->getType(),
                'type' => TaskConstant::TYPE_ARCHITECT,
                'status' => TaskConstant::TASK_STATUS_UNASSIGNED,
                'details' => $data['details']['details'],
            ];
        } else {
            $task['fullname'] = $saleNode->getFullname();
            $task['team_name'] = $saleNode->getParent()->getName();
        }
        return $task;
    }

    protected function formatSaleNodeForExport($period, SaleNode $saleNode)
    {
        $prefix = [
            'period' => $period,
            'fullname' => $saleNode->getFullname(),
            'team_name' => $saleNode->getParent() ? $saleNode->getParent()->getName() : '',
            'parent_fullname' => $saleNode->getParent() ? $saleNode->getParent()->getLeader()->getFullname() : '',
            'target_id' => $saleNode->getSaleId(),
            'parent_id' => $saleNode->getParent() ? $saleNode->getParent()->getTeamId() : '',
            'channel' => $saleNode->getParent() ? $saleNode->getParent()->getType() : '',
            'level' => TaskConstant::LEVEL_SALE,
        ];
        $data = $saleNode->getExtraData();
        $details = $data['details']['details'];
        foreach ($details as $name => &$money) {
            $money = TaskConstant::convertMoney($money);
        }
        unset($money);
        return array_merge($prefix, $details);
    }

    public function generateDownloadFile($data, $filename = '销售任务数据')
    {
        $exportFileName = "{$filename}_" . Carbon::now()->format("YmdHis") . ".xlsx";
        $exportFilePath = "/tmp/{$exportFileName}";
        $detailHeaders = [];

        $row = $data[0];
        foreach ($row as $name => $money) {
            if (array_key_exists($name, TaskConstant::$taskDetailDict)) {
                $detailHeaders[] = TaskConstant::$taskDetailDict[$name] . "（千元）";
            }
        }
        $headers = array_merge(
            TaskConstant::$downloadHeaders,
            $detailHeaders
        );

        $rows[] = $headers;
        foreach ($data as $datum) {
            $rows[] = array_values($datum);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // 隐藏任务自增id等字段
        for ($i = 4; $i < 8; $i++) {
            $column = chr(ord('A') + $i);
            $sheet->getColumnDimension($column)->setVisible(false);
        }
        $sheet->fromArray($rows);
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($exportFilePath);
        return [
            'file' => $exportFilePath,
            'name' => $exportFileName,
            'header' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ];
    }
}

<?php

namespace App\Services\Task;

use App\Constant\ArchitectConstant;
use App\Constant\TaskConstant;
use App\Exceptions\API\Forbidden;
use App\Exceptions\API\ValidationFailed;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\MicroService\TaskClient;
use App\Services\Common\ConfigService;
use App\Services\Tree\SaleNode;
use App\Services\Tree\TeamNode;
use App\Services\Tree\Tree;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class TaskService
 * @package App\Services\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class TaskService
{

    /**
     * @var ArchitectService
     */
    protected $architectService;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var TaskClient
     */
    protected $taskClient;

    public function __construct(
        ArchitectService $architectService,
        ConfigService $configService,
        TaskClient $taskClient
    ) {
        $this->architectService = $architectService;
        $this->configService = $configService;
        $this->taskClient = $taskClient;
    }

    public function getTaskDimension($channel)
    {
        $key = TaskConstant::getTaskDimensionConfigKey($channel);
        $data = $this->configService->getConfig($key);
        if (!array_key_exists($key, $data)) {
            throw new \RuntimeException("$key config does not exists.");
        }
        return $data[$key];
    }

    /**
     * 校验提交的任务分配数据，是否正确
     * @param $task
     * @param $detailConfigs
     * @param array $subTasks
     */
    public function validate($task, $detailConfigs, $subTasks = [])
    {
        $this->validateTask($task, $detailConfigs);
        if (!empty($subTasks)) {
            $this->validateTotal($task, $subTasks, $detailConfigs);
        }
    }

    protected function validateTask($task, $detailConfigs)
    {
        $details = $task['details'];
        if (count($details) !== count($detailConfigs)) {
            throw new ValidationFailed("填写的任务维度跟需要填写的任务不一致");
        }
        if (isset($task['team_name']) && isset($task['fullname'])) {
            $targetName = $task['team_name'] . " - " . $task['fullname'];
        } else {
            $targetName = $task['target_id'];
        }
        foreach ($detailConfigs as $detailConfig) {
            if (!array_key_exists($detailConfig['name'], $details)) {
                $name = TaskConstant::revertDetail($detailConfig['name']);
                throw new ValidationFailed("{$targetName}需要{$name}参数");
            }
        }
        $video = (int)$details['brand.video'];
        $news = (int)$details['brand.news'];
        if (array_key_exists('total', $details)) {
            //总体任务 >= 视频 + 新闻
            $total = (int)$details['total'];
            if ($total < $video + $news) {
                $fmt = "%s的%s要大于等于%s加%s";
                throw new ValidationFailed(sprintf(
                    $fmt,
                    $targetName,
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('total')],
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('brand.video')],
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('brand.news')]
                ));
            }
        }
        if (array_key_exists('brand.total', $details)) {
            //品牌任务 >= 视频 + 新闻
            $brand = (int)$details['brand.total'];
            if ($brand < $video + $news) {
                $fmt = "%s的%s要大于等于%s加%s";
                throw new ValidationFailed(sprintf(
                    $fmt,
                    $targetName,
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('brand.total')],
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('brand.video')],
                    TaskConstant::$taskDetailDict[TaskConstant::revertDetail('brand.news')]
                ));
            }
        }
    }

    protected function validateTotal($task, $subTasks, $detailConfigs)
    {
        if (empty($subTasks)) {
            return;
        }
        $details = $task['details'];
        $total = [];
        foreach ($details as $name => $money) {
            $total[$name] = 0;
        }
        foreach ($subTasks as $subTask) {
            $this->validateTask($subTask, $detailConfigs);
            $subDetails = $subTask['details'];
            foreach ($subDetails as $name => $money) {
                $total[$name] += (int)$money;
            }
        }
        foreach ($detailConfigs as $detailConfig) {
            $subTotal = (int)$total[$detailConfig['name']];
            $taskTotal = (int)$details[$detailConfig['name']];
            if ($subTotal != $taskTotal) {
                $fmt = "%s分配的金额与总金额不匹配";
                $dimension = TaskConstant::$taskDetailDict[TaskConstant::revertDetail($detailConfig['name'])];
                throw new ValidationFailed(sprintf($fmt, $dimension));
            }
        }
    }

    /**
     * 根据period和targetIds批量获取任务
     * @param $period
     * @param $targetIds
     * @return mixed
     */
    public function queryTask($period, $targetIds)
    {
        if (is_array($targetIds)) {
            $targetIds = implode(',', $targetIds);
        }
        $params = [
            'target_id' => $targetIds,
            'period' => $period,
        ];
        $ret = $this->taskClient->queryTask($params);
        $pageInfo = $ret['page_info'];
        $total = $pageInfo['total_number'];
        if (count($ret['data']) >= $total) {
            return $ret['data'];
        }
        $params['per_page'] = $total;
        $ret = $this->taskClient->queryTask($params);
        return $ret['data'];
    }

    public function checkCanLock(User $user, Tree $tree, $teamId)
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return $this->canOperatorLock($user, $tree, $teamId);
        }
        return $this->canSaleLock($user, $tree, $teamId);
    }

    protected function canOperatorLock(User $user, Tree $tree, $teamId)
    {
        if ($tree->getRoot()->getTeamId() != $teamId) {
            throw new ValidationFailed("运营只能锁定总任务");
        }
        /**
         * @var QueryService $queryService
         */
        $queryService = app()->make(QueryService::class);
        $centerTeamList = $queryService->getOperatorCenterTeamNodeList($tree);
        foreach ($centerTeamList as $teamNode) {
            $this->canTeamTaskLock($teamNode);
            $children = $teamNode->getChildren();
            foreach ($children as $child) {
                $this->canTeamTaskLock($child, "小组");
                $sales = $child->getSales();
                foreach ($sales as $sale) {
                    $this->canSaleTaskLock($sale);
                }
            }
        }
    }

    /**
     * @param TeamNode $teamNode
     * @param string $taskDescription
     * @param bool $checkTaskLock - false代表不检查当前节点的任务是否锁定
     */
    protected function canTeamTaskLock(TeamNode $teamNode, $taskDescription = "总监组", $checkTaskLock = true)
    {
        $data = $teamNode->getExtraData();
        if (empty($data['task'])) {
            throw new ValidationFailed("{$taskDescription}任务还未分配");
        }
        if (!$checkTaskLock) {
            return;
        }
        $task = $data['task'];
        if ($task['status'] != TaskConstant::TASK_STATUS_LOCKED) {
            $teamName = $teamNode->getName();
            if ($teamNode->getLeader()) {
                $teamName = $teamName . " - " . $teamNode->getLeader()->getName();
            }
            throw new ValidationFailed("{$taskDescription}{$teamName}任务还未锁定");
        }
    }

    protected function canSaleTaskLock(SaleNode $saleNode)
    {
        $data = $saleNode->getExtraData();
        if (empty($data['task'])) {
            $saleName = $saleNode->getParent()->getName() . " - " . $saleNode->getFullname();
            throw new ValidationFailed("{$saleName}任务还未分配");
        }
    }

    protected function canSaleLock(User $user, Tree $tree, $teamId)
    {
        $teamList = $tree->bfs();
        $team = null;
        foreach ($teamList as $teamNode) {
            if ($teamNode->getLevel() != ArchitectConstant::TEAM_LEVEL_DIRECTOR) {
                continue;
            }
            if ($teamNode->getTeamId() != $teamId) {
                continue;
            }
            $team = $teamNode;
        }
        if (empty($team) || $team->getLeader()->getSaleId() != $user->getSaleId()) {
            throw new ValidationFailed("没有权限锁定{$teamId}");
        }
        $this->canTeamTaskLock($team, "总监组", false);
        $children = $team->getChildren();
        foreach ($children as $child) {
            $this->canTeamTaskLock($child, "小组", false);
            $sales = $child->getSales();
            foreach ($sales as $sale) {
                $this->canSaleTaskLock($sale);
            }
        }
    }

    public function validatePeriod($period)
    {
        $nextQ = Carbon::create()->addQuarter();
        $year = $nextQ->year;
        $quarter = $nextQ->quarter;
        $nextPeriod = sprintf("%dQ%d", $year, $quarter);
        if ($nextPeriod !== $period) {
            throw new Forbidden("只能分配{$nextPeriod}的任务");
        }
    }

    public function createSubTaskExportFile($tasks, $detailConfigs)
    {
        $user = Auth::user();
        $filename = "销售任务分配_{$user->getRtx()}_" . date('YmdHis') . ".xlsx";
        $path = "/tmp/{$filename}";

        $preHeaders = ["姓名", "小组", "考核周期",];
        $afterHeaders = ["任务自增id", "任务对象id", "任务对象父级id", "任务类型", "销售id"];
        $detailHeaders = [];
        foreach ($detailConfigs as $config) {
            $detailHeaders[] = TaskConstant::$taskDetailDict[$config['name']] . "（千元）";
        }
        $headers = array_merge($preHeaders, $detailHeaders, $afterHeaders);
        $data[] = $headers;
        $visibleColumnCount = count($preHeaders) + count($detailConfigs);
        foreach ($tasks as $task) {
            $preData = [
                $task['fullname'] ?? "",
                $task['team_name'] ?? "",
                $task['period'] ?? "",
            ];
            $afterData = [
                $task['id'] ?? "",
                $task['target_id'] ?? "",
                $task['parent_id'] ?? "",
                $task['type'] ?? "",
                $task['sale_id'] ?? "",
            ];
            $detailData = [];
            $details = $task['details'];
            foreach ($detailConfigs as $config) {
                $detailData[] = $details[$config['name']];
            }
            $data[] = array_merge($preData, $detailData, $afterData);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data);
        $columnIterator = $sheet->getColumnIterator();
        foreach ($columnIterator as $column) {
            if ($visibleColumnCount) {
                $visibleColumnCount--;
                continue;
            }
            $index = $column->getColumnIndex();
            $sheet->getColumnDimension($index)->setVisible(false);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $httpHeaders = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return [$filename, $path, $httpHeaders];
    }

    public function readTaskFromFile($file, $dimensions)
    {
        $sheet = IOFactory::load($file->path());
        $data = $sheet->getActiveSheet()->toArray();
        array_shift($data);     //去掉excel的header
        $tasks = [];
        /*
        $headers = ["季度", "姓名", "所在组", "上级姓名", "target_id", "parent_id", "channel", "level"];
        */
        foreach ($data as $row) {
            $details = [];
            $period = array_shift($row);
            array_shift($row);
            array_shift($row);
            array_shift($row);
            $targetId = array_shift($row);
            $parentId = array_shift($row);
            $channel = array_shift($row);
            $level = array_shift($row);
            foreach ($dimensions as $dimension) {
                $money = array_shift($row);
                $details[$dimension['name']] = TaskConstant::revertMoney($money);
            }
            $tasks[] = [
                'target_id' => $targetId,
                'parent_id' => $parentId,
                'period' => $period,
                'level' => $level,
                'channel' => $channel,
                'type' => TaskConstant::TYPE_ARCHITECT,
                'details' => $details,
            ];
        }
        return $tasks;
    }

    /**
     * @param $period
     * @param TeamNode $teamNode
     */
    public function addTeamTaskDetailsUp($period, TeamNode $teamNode)
    {
        $data = $teamNode->getExtraData();
        $details = $data['task']['details'];
        if (empty($details)) {
            return;
        }
        $parent = $teamNode->getParent();
        while ($parent) {
            if ($parent->getLevel() <= ArchitectConstant::TEAM_LEVEL_NONE) {
                break;
            }
            $parentData = $parent->getExtraData();
            $task = $parentData['task'] ?? [];
            if (empty($task)) {
                $task = [
                    'period' => $period,
                    'target_id' => $parent->getTeamId(),
                    'parent_id' => $parent->getPid(),
                    'fullname' => $parent->getLeader()->getFullname(),
                    'team_name' => $parent->getName(),
                    'level' => TaskConstant::convertArchitectLevelToTaskLevel($parent->getLevel()),
                    'channel' => $parent->getType(),
                    'type' => TaskConstant::TYPE_ARCHITECT,
                    'status' => TaskConstant::TASK_STATUS_UNASSIGNED,
                    'details' => $details,
                ];
            } else {
                $parentDetails = $task['details'];
                foreach ($details as $name => $money) {
                    if (array_key_exists($name, $parentDetails)) {
                        $parentDetails[$name] += $money;
                    } else {
                        $parentDetails[$name] = $money;
                    }
                }
                $task['details'] = $parentDetails;
            }
            $parentData['task'] = $task;
            $parent->setExtraData($parentData);
            $parent = $parent->getParent();
        }
    }

    /**
     * @param User $user
     * @param $teamId
     * @param $period
     * @return bool
     */
    public function shouldAttachForecastInfo(User $user, $teamId, $period)
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return true;
        }
        $teamInfo = $this->architectService->getTeamInfo($teamId, $period);
        if (empty($teamInfo)) {
            throw new ValidationFailed("小组{$teamId}在{$period}不存在");
        }
        $level = (int)$teamInfo['level'];
        if ($level === User::ROLE_DIRECTOR) {
            return true;
        }
        return false;
    }

    /**
     * 给任务数据附加总监预估数据
     * @param User $user
     * @param $tasks
     * @param $period
     * @return array
     */
    public function attachForecastInfo(User $user, $tasks, $period)
    {
        if ($user->isOperator() || $user->isAdmin()) {
            $forecasts = $this->getDirectorForecast($tasks, $period);
        } else {
            $forecasts = $this->getLeaderForecast($tasks, $period);
        }

        foreach ($tasks as &$task) {
            $targetId = $task['target_id'];
            if (array_key_exists($targetId, $forecasts)) {
                $forecast = $forecasts[$targetId];
                $task['forecast'] = $forecast;
            }
        }
        return $tasks;
    }

    protected function getDirectorForecast($tasks, $period)
    {
        /**
         * @var ArchitectClient $architectClient
         * @var TaskClient $TaskClient
         */
        $architectClient = app()->make(ArchitectClient::class);
        $TaskClient = app()->make(TaskClient::class);
        $directorTeamIds = $leaderTeamIds = $parentForecastMap = $ret = [];
        foreach ($tasks as $task) {
            $directorTeamIds[] = $task['target_id'];
        }
        $params = [
            'pid' => implode(',', $directorTeamIds),
            'level' => User::ROLE_LEADER,
            'per_page' => 10000,
        ];
        $ret = $architectClient->getTeamList($params);
        $teams = $ret['data'];
        foreach ($teams as $team) {
            $leaderTeamIds[] = $team['team_id'];
            $parentForecastMap[$team['pid']][] = $team['team_id'];
        }

        list($year, $quarter) = explode('Q', $period, 2);
        $forecasts = $TaskClient->getTeamForecast($leaderTeamIds, $year, $quarter);

        foreach ($parentForecastMap as $pid => $teamIds) {
            //$pid: 总监组id，$teamIds: 小组id列表
            foreach ($teamIds as $teamId) {
                if (!array_key_exists($pid, $ret)) {
                    //数组不存在，直接取一个leader组的预估数据
                    $ret[$pid] = $forecasts[$teamId] ?? [];
                    continue;
                }
                $info = $ret[$pid];
                $leaderInfo = $forecasts[$teamId] ?? [];
                if (empty($leaderInfo)) {
                    continue;
                }
                //取一个leader组的预估数据，并且跟总监组的预估数据相加
                foreach ($leaderInfo as $k => $v) {
                    $info[$k] = ($info[$k] ?? 0) + $v;
                }
                $ret[$pid] = $info;
            }
        }
        return $ret;
    }

    protected function getLeaderForecast($tasks, $period)
    {
        /**
         * @var TaskClient $TaskClient
         */
        $TaskClient = app()->make(TaskClient::class);
        $leaderTeamIds = [];
        foreach ($tasks as $task) {
            $leaderTeamIds[] = $task['target_id'];
        }
        list($year, $quarter) = explode('Q', $period, 2);
        $forecasts = $TaskClient->getTeamForecast($leaderTeamIds, $year, $quarter);
        return $forecasts;
    }
}

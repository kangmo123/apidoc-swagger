<?php

namespace App\Http\Controllers\Task;

use App\Constant\ArchitectConstant;
use App\Constant\TaskConstant;
use App\Exceptions\API\ValidationFailed;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Common\ConfigService;
use App\Services\Task\ArchitectService;
use App\Services\Task\QueryService;
use App\Services\Task\TaskService;
use App\Services\Tree\TeamNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TotalController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class SubController extends Controller
{

    /**
     * @api {get} /task/sub
     * @apiDescription 获取下级分配的任务
     * @apiGroup Task/Sub
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": [
     * {
     * "period": "2019",
     * "target_id": "4453DBA4-8642-11E7-B486-ECF4BBC3BE1C",
     * "parent_id": "direct",
     * "name": "KA四组",
     * "status": 0,
     * "details": [
     * {
     * "name": "brand",
     * "comment": "品牌任务",
     * "money": null
     * },
     * {
     * "name": "video",
     * "comment": "视频任务",
     * "money": null
     * },
     * {
     * "name": "app",
     * "comment": "新闻APP任务",
     * "money": null
     * }
     * ]
     * }
     * ]
     * }
     *
     * @param Request $request
     * @param TaskClient $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request, TaskClient $client)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        /**
         * @var ArchitectService $architectService
         * @var TaskService $taskService
         * @var User $user
         * @var QueryService $queryService
         */
        $architectService = app()->make(ArchitectService::class);
        $taskService = app()->make(TaskService::class);
        $queryService = app()->make(QueryService::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);

        if ($teamId == $tree->getRoot()->getTeamId()) {
            $centerTeamList = $queryService->getOperatorCenterTeamNodeList($tree);
            $tasks = $queryService->formatTeamNodeListForList($period, $centerTeamList);
        } else {
            $team = null;
            $teamList = $tree->bfs();
            foreach ($teamList as $teamNode) {
                if ($teamNode->getTeamId() == $teamId) {
                    $team = $teamNode;
                }
            }
            $children = $team->getChildren();
            $sales = $team->getSales();
            if ($children->isEmpty()) {
                //直接获取销售的任务
                $tasks = $queryService->formatSaleNodeListForList($period, $sales);
            } else {
                //获取下属组的任务
                $tasks = $queryService->formatTeamNodeListForList($period, $children);
            }
        }
        if ($taskService->shouldAttachForecastInfo($user, $teamId, $period)) {
            $tasks = $taskService->attachForecastInfo($user, $tasks, $period);
        }
        return $this->success($tasks);
    }

    /**
     * @api {put} /task/sub
     * @apiDescription 更新任务分配
     * @apiGroup Task/Sub
     * @apiVersion 1.0.0
     * @apiHeader Content-Type=x-www-form-urlencoded
     * @apiParam {String} period 季度
     * @apiParam {Integer} [brand] 品牌任务金额（不同的人，任务纬度可能不一样，从总任务中取）
     * @apiParam {Integer} [video] 视频任务金额
     * @apiParam {Integer} [app] 新闻app任务金额
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "data": {
     * "id": 1,
     * "period": "2019Q1",
     * "target_id": "direct",
     * "parent_id": null,
     * "type": 0,
     * "status": 0,
     * "details": [
     * {
     * "id": 1,
     * "name": "brand",
     * "money": 4444,
     * },
     * {
     * "id": 2,
     * "name": "video",
     * "money": 5555,
     * },
     * {
     * "id": 3,
     * "name": "app",
     * "money": 6666,
     * }
     * ]
     * },
     * "code": 0,
     * "msg": "OK"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        $taskData = $request->input('tasks');
        /**
         * @var User $user
         * @var ConfigService $configService
         * @var ArchitectService $architectService
         * @var TaskService $taskService
         * @var QueryService $queryService
         * @var TaskClient $taskClient
         */
        $architectService = app()->make(ArchitectService::class);
        $taskService = app()->make(TaskService::class);
        $queryService = app()->make(QueryService::class);
        $taskClient = app()->make(TaskClient::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);

        $mineTaskNode = null;
        $updateForNation = false;
        if ($taskData[0]['level'] == TaskConstant::LEVEL_CENTER) {
            //给总监组制定任务，当前任务是总任务
            $mineTaskNode = $tree->getRoot();
            $updateForNation = true;
        } else {
            $teamList = $tree->bfs();
            foreach ($teamList as $teamNode) {
                /**
                 * @var TeamNode $teamNode
                 */
                if ($teamNode->getTeamId() == $teamId) {
                    $mineTaskNode = $teamNode;
                }
            }
        }
        $data = $mineTaskNode->getExtraData();
        $mineTask = $data['task'] ?? [];
        if (empty($mineTask)) {
            throw new ValidationFailed('父任务还未制定，不能制定子任务');
        }
        if ($mineTask['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            throw new ValidationFailed('任务已锁定，请先解锁后再分配');
        }

        foreach ($taskData as &$datum) {
            $details = $datum['details'];
            $tmp = [];
            foreach ($details as $name => $money) {
                $name = TaskConstant::convertDetail($name);
                $tmp[$name] = $money;
            }
            $datum['details'] = $tmp;
        }
        unset($datum);
        //校验任务数据
        $mineTaskDetails = $mineTask['details'];
        $tmp = [];
        $detailConfigs = [];
        foreach ($mineTaskDetails as $name => $money) {
            $name = TaskConstant::convertDetail($name);
            $detailConfigs[] = ['name' => $name];
            $tmp[$name] = $money;
        }
        $mineTask['details'] = $tmp;
        $taskService->validate($mineTask, $detailConfigs, $taskData);

        if ($updateForNation) {
            $taskMap = [];
            foreach ($taskData as $datum) {
                $hash = $datum['target_id'] . "|" . $datum['parent_id'];
                $taskMap[$hash] = $datum;
            }
            $teamList = $queryService->getOperatorCenterTeamNodeList($tree);
            foreach ($teamList as $teamNode) {
                $hash = $teamNode->getTaskHash();
                if (array_key_exists($hash, $taskMap)) {
                    $task = $taskMap[$hash];
                    $data = ['task' => $task, 'details' => ['details' => $task['details']]];
                    $teamNode->setExtraData($data);
                    $taskService->addTeamTaskDetailsUp($period, $teamNode);
                }
            }
            $teamList = $tree->bfs();
            $nationTasks = [];
            foreach ($teamList as $teamNode) {
                if ($teamNode->getLevel() >= ArchitectConstant::TEAM_LEVEL_DIRECTOR) {
                    continue;
                }
                $data = $teamNode->getExtraData();
                $task = $data['task'] ?? [];
                if (!empty($task)) {
                    $nationTasks[] = $task;
                }
            }
            $taskData = array_merge($nationTasks, $taskData);
        }

        $params = [
            'period' => $period,
            'team_id' => $teamId,
            'tasks' => $taskData,
        ];
        $ret = $taskClient->updateSubTask($params);
        return $this->success($ret['data']);
    }

    /**
     * @api {get} /task/sub/export
     * @apiDescription 下载任务分配excel
     * @apiGroup Task/Sub
     * @apiVersion 1.0.0
     * @apiHeader Content-Type=x-www-form-urlencoded
     * @apiParam {String} period 季度
     * @apiParam {String} parent_id 要导出的任务的对象id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        /**
         * @var ArchitectService $architectService
         * @var User $user
         * @var QueryService $queryService
         */
        $architectService = app()->make(ArchitectService::class);
        $queryService = app()->make(QueryService::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);

        if ($teamId == $tree->getRoot()->getTeamId()) {
            $centerTeamList = $queryService->getOperatorCenterTeamNodeList($tree);
            $tasks = $queryService->formatTeamNodeListForExport($period, $centerTeamList, false);
        } else {
            $team = null;
            $teamList = $tree->bfs();
            foreach ($teamList as $teamNode) {
                if ($teamNode->getTeamId() == $teamId) {
                    $team = $teamNode;
                }
            }
            $children = $team->getChildren();
            $sales = $team->getSales();
            if ($children->isEmpty()) {
                //直接获取销售的任务
                $tasks = $queryService->formatSaleNodeListForExport($period, $sales);
            } else {
                //获取下属组的任务
                $tasks = $queryService->formatTeamNodeListForExport($period, $children, false);
            }
        }
        $downloadInfo = $queryService->generateDownloadFile($tasks, "销售任务分配");
        return response()->download($downloadInfo['file'], $downloadInfo['name'], $downloadInfo['header']);
    }

    /**
     * @api {post} /task/sub/import
     * @apiDescription 上传任务分配excel
     * @apiGroup Task/Sub
     * @apiVersion 1.0.0
     * @apiHeader Content-Type=multipart/form-data
     * @apiParam {String} period 季度
     * @apiParam {String} parent_id 要导出的任务的对象id
     * @apiParam {File } file 上传的文件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $this->validate($request, [
            'period' => 'required|string',
            'team_id' => 'required|string'
        ]);
        $file = $request->file('file');
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        /**
         * @var User $user
         * @var TaskService $taskService
         * @var QueryService $queryService
         * @var ArchitectService $architectService
         * @var TaskClient $taskClient
         */
        $user = Auth::user();
        $taskService = app()->make(TaskService::class);
        $queryService = app()->make(QueryService::class);
        $architectService = app()->make(ArchitectService::class);
        $taskClient = app()->make(TaskClient::class);

        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);

        $root = $tree->getRoot();
        $data = $root->getExtraData();
        $details = $data['details']['details'];
        $dimensions = [];
        foreach ($details as $name => $money) {
            $dimensions[] = ['name' => $name];
        }
        $tasks = $taskService->readTaskFromFile($file, $dimensions);
        $mineTaskNode = null;
        if ($tasks[0]['level'] == TaskConstant::LEVEL_CENTER) {
            //给总监组制定任务，当前任务是总任务
            $mineTaskNode = $tree->getRoot();
        } else {
            $teamList = $tree->bfs();
            foreach ($teamList as $teamNode) {
                /**
                 * @var TeamNode $teamNode
                 */
                if ($teamNode->getTeamId() == $teamId) {
                    $mineTaskNode = $teamNode;
                }
            }
        }
        $data = $mineTaskNode->getExtraData();
        $mineTask = $data['task'] ?? [];
        if (empty($mineTask)) {
            throw new ValidationFailed('父任务还未制定，不能制定子任务');
        }
        if ($mineTask['status'] == TaskConstant::TASK_STATUS_LOCKED) {
            throw new ValidationFailed('任务已锁定，请先解锁后再分配');
        }

        foreach ($tasks as &$datum) {
            $details = $datum['details'];
            $tmp = [];
            foreach ($details as $name => $money) {
                $name = TaskConstant::convertDetail($name);
                $tmp[$name] = $money;
            }
            $datum['details'] = $tmp;
        }
        unset($datum);
        //校验任务数据
        $mineTaskDetails = $mineTask['details'];
        $tmp = [];
        $detailConfigs = [];
        foreach ($mineTaskDetails as $name => $money) {
            $name = TaskConstant::convertDetail($name);
            $detailConfigs[] = ['name' => $name];
            $tmp[$name] = $money;
        }
        $mineTask['details'] = $tmp;
        $taskService->validate($mineTask, $detailConfigs, $tasks);
        $params = [
            'period' => $period,
            'team_id' => $teamId,
            'tasks' => $tasks,
        ];
        $ret = $taskClient->updateSubTask($params);
        return $this->success($ret['data']);
    }
}

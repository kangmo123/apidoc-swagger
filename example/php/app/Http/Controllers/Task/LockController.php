<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Task\ArchitectService;
use App\Services\Task\QueryService;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LockController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class LockController extends Controller
{

    /**
     * @api {post} /task/lock
     * @apiDescription 锁定任务
     * @apiGroup Task/Lock
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     * @apiParam {String} team_id 要锁定的任务
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": [
     * ]
     * }
     *
     * @param Request $request
     * @param TaskClient $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function lock(Request $request, TaskClient $client)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        /**
         * @var User $user
         * @var ArchitectService $architectService
         * @var QueryService $queryService
         * @var TaskService $taskService
         */
        $architectService = app()->make(ArchitectService::class);
        $queryService = app()->make(QueryService::class);
        $taskService = app()->make(TaskService::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);
        $taskService->checkCanLock($user, $tree, $teamId);

        $teamList = $tree->bfs();
        if ($user->isOperator() || $user->isAdmin()) {
            $targetIds = $tree->getTeamAndSaleIds($teamList);
            $teamId = implode(',', $targetIds);
        } else {
            $team = null;
            foreach ($teamList as $teamNode) {
                if ($teamNode->getTeamId() == $teamId) {
                    $team = $teamNode;
                }
            }
            $teamList = $tree->bfs($team, true);
            $targetIds = $tree->getTeamAndSaleIds($teamList);
            $teamId = implode(',', $targetIds);
        }
        //获取数据
        $params = [
            'period' => $period,
            'team_id' => $teamId
        ];
        $ret = $client->lock($params);
        return $this->success($ret['data']);
    }

    /**
     * @api {post} /task/unlock
     * @apiDescription 解锁任务
     * @apiGroup Task/Lock
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     * @apiParam {String} team_id 要解锁的任务
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "data": {
     * },
     * "code": 0,
     * "msg": "OK"
     * }
     *
     * @param Request $request
     * @param TaskClient $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlock(Request $request, TaskClient $client)
    {
        $teamId = $request->input('team_id');
        $period = $request->input('period');
        /**
         * @var User $user
         * @var ArchitectService $architectService
         * @var QueryService $queryService
         * @var TaskService $taskService
         */
        $architectService = app()->make(ArchitectService::class);
        $queryService = app()->make(QueryService::class);
        $taskService = app()->make(TaskService::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);
        $taskService->checkCanLock($user, $tree, $teamId);

        $teamList = $tree->bfs();
        if ($user->isOperator() || $user->isAdmin()) {
            $targetIds = $tree->getTeamAndSaleIds($teamList);
            $teamId = implode(',', $targetIds);
        } else {
            $team = null;
            foreach ($teamList as $teamNode) {
                if ($teamNode->getTeamId() == $teamId) {
                    $team = $teamNode;
                }
            }
            $teamList = $tree->bfs($team, true);
            $targetIds = $tree->getTeamAndSaleIds($teamList);
            $teamId = implode(',', $targetIds);
        }
        //获取数据
        $params = [
            'period' => $period,
            'team_id' => $teamId
        ];
        $ret = $client->unlock($params);
        return $this->success($ret['data']);
    }
}

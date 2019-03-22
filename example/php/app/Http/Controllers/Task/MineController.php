<?php

namespace App\Http\Controllers\Task;

use App\Constant\TaskConstant;
use App\Exceptions\API\ValidationFailed;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Task\ArchitectService;
use App\Services\Task\QueryService;
use App\Services\Tree\SaleNode;
use App\Services\Tree\TeamNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TotalController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class MineController extends Controller
{

    /**
     * @api {get} /task/mine
     * @apiDescription 获取本人的任务
     * @apiGroup Task/Mine
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     * @apiParam {String} parent_id 任务对象id
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": {
     * "id": 2,
     * "period": "2019Q1",
     * "target_id": "4453DBA4-8642-11E7-B486-ECF4BBC3BE1C",
     * "parent_id": "direct",
     * "type": 1,
     * "status": 1,
     * "details": {
     * "brand": 1024,
     * "video": 2048,
     * "app": 512
     * },
     * "team_id": "4453DBA4-8642-11E7-B486-ECF4BBC3BE1C",
     * "team_name": "KA四组",
     * "level": 20,
     * "sale_id": "74EAE6DC-5EC0-DD11-AB7A-001D096CF989",
     * "rtx": "jamesli",
     * "name": "李捷",
     * "fullname": "jamesli(李捷)"
     * }
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
        $queryService = app()->make(QueryService::class);

        $user = Auth::user();
        $tree = $queryService->query($user, $period, $teamId, false);
        $architectService->checkTreeAuth($user, $teamId, $tree);

        //获取team_id的任务
        $teamList = $tree->bfs();
        /**
         * @var TeamNode $team
         * @var SaleNode $sale
         */
        $team = $sale = null;
        foreach ($teamList as $teamNode) {
            if ($teamNode->getTeamId() == $teamId) {
                $team = $teamNode;
            }
        }
        $mineTask = [];
        if ($team->getLeader()->getSaleId() == $user->getSaleId()) {
            $data = $team->getExtraData();
            $mineTask = $data['task'] ?? [];
            if (empty($mineTask)) {
                throw new ValidationFailed('父任务还未制定，不能制定子任务');
            }
            $mineTask['fullname'] = $team->getLeader()->getFullname();
            $mineTask['team_name'] = $team->getName();
        } else {
            $sales = $team->getSales();
            foreach ($sales as $sale) {
                if ($sale->getSaleId() == $user->getSaleId()) {
                    $data = $sale->getExtraData();
                    $mineTask = $data['task'] ?? [];
                    if (empty($mineTask)) {
                        throw new ValidationFailed('父任务还未制定，不能制定子任务');
                    }
                    $mineTask['fullname'] = $sale->getFullname();
                    $mineTask['team_name'] = $team->getName();
                }
            }
        }
        $details = $mineTask['details'];
        $tmp = [];
        foreach ($details as $name => $money) {
            $name = TaskConstant::revertDetail($name);
            $tmp[$name] = $money;
        }
        $mineTask['details'] = $tmp;
        return $this->success($mineTask);
    }
}

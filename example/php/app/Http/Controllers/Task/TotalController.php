<?php

namespace App\Http\Controllers\Task;

use App\Constant\TaskConstant;
use App\Exceptions\API\Forbidden;
use App\Exceptions\API\ValidationFailed;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Task\QueryService;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TotalController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class TotalController extends Controller
{

    /**
     * @api {get} /task/total
     * @apiDescription 获取当前总任务
     * @apiGroup Task/Total
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": {
     * "id": 1,
     * "period": "2019Q1",
     * "target_id": "direct",
     * "type": 0,
     * "status": 0,
     * "details": [
     * {
     * "id": 1,
     * "name": "brand",
     * "money": 111,
     * },
     * {
     * "id": 2,
     * "name": "video",
     * "money": 222,
     * },
     * {
     * "id": 3,
     * "name": "app",
     * "money": 333,
     * }
     * ]
     * }
     * }
     *
     * @param Request $request
     * @param TaskClient $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request, TaskClient $client)
    {
        $period = $request->input('period');
        $teamId = $request->input('team_id');
        $params = [
            'period' => $period,
            'target_id' => $teamId
        ];
        $ret = $client->getTotalTask($params);
        $data = $ret['data'];
        $details = $data['details'];
        $revertDetails = [];
        foreach ($details as $name => $money) {
            $name = TaskConstant::revertDetail($name);
            $revertDetails[$name] = $money;
        }
        $data['details'] = $revertDetails;
        return $this->success($data);
    }

    /**
     * @api {put} /task/total
     * @apiDescription 更新总任务，运营人员才能操作
     * @apiGroup Task/Total
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
     * @param TaskClient $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TaskClient $client)
    {
        /**
         * @var User $user
         */
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOperator()) {
            throw new Forbidden('没有权限制定总任务');
        }

        $params = $request->all();
        $period = $params['period'];
        $teamId = $params['team_id'];
        unset($params['period']);
        unset($params['team_id']);

        $details = [];
        foreach ($params as $name => $money) {
            //做一次detail的名称转换，兼容前端的老的name
            $name = TaskConstant::convertDetail($name);
            $details[$name] = $money;
        }
        /**
         * @var TaskService $taskService
         * @var QueryService $queryService
         */
        $taskService = app()->make(TaskService::class);
        $queryService = app()->make(QueryService::class);
        $tree = $queryService->query($user, $period, $teamId, false);
        $teamList = $tree->bfs();
        $team = null;
        foreach ($teamList as $teamNode) {
            if ($teamNode->getTeamId() == $teamId) {
                $team = $teamNode;
            }
        }
        if (empty($team)) {
            throw new ValidationFailed("总任务ID配置错误, period: {$period}, team id: {$teamId}");
        }
        $root = $tree->getRoot();
        $data = $root->getExtraData();
        $totalDetails = $data['details']['details'];
        $dimensions = [];
        foreach ($totalDetails as $name => $money) {
            $name = TaskConstant::convertDetail($name);
            $dimensions[] = ['name' => $name];
        }

        //校验，分配任务只能分配下Q的任务
        //$taskService->validatePeriod($period);
        $params = [
            'period' => $period,
            'target_id' => $teamId,
            'type' => TaskConstant::TYPE_TOTAL,
            'channel' => $team->getType(),
            'details' => $details
        ];
        $taskService->validate($params, $dimensions);
        $ret = $client->updateTotalTask($params);
        //TODO: 更新总任务，将这一系的任务状态都设置为0-未分配
        return $this->success($ret['data']);
    }
}

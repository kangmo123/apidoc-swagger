<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Task\QueryService;
use App\Services\Task\StepService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Class StepController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class StepController extends Controller
{

    /**
     * @api {get} /task/steps
     * @apiDescription 获取目前所有的步骤状态
     * @apiGroup Task/Step
     * @apiVersion 1.0.0
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": {
     * "1": "waiting",
     * "2": "total_task_confirming",
     * "3": "operator_assigning",
     * "4": "director_assigning",
     * "5": "leader_assigning",
     * "6": "director_confirming",
     * "7": "operator_confirming",
     * "8": "finished"
     * }
     * }
     * @return \Illuminate\Http\JsonResponse
     */
    public function steps()
    {
        $steps = StepService::$steps;
        return $this->success($steps);
    }

    /**
     * @api {get} /task/steps/current
     * @apiDescription 获取当前用户所处的任务步骤
     * @apiParam {String} period 季度
     * @apiParam {String} parent_id 所选的组织架构id
     * @apiParam {Integer} level 组织架构中所处的层级
     * @apiGroup Task/Step
     * @apiVersion 1.0.0
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "success",
     * "data": {
     * "step": 7,
     * "info": "operator_confirming"
     * }
     * }
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentStep(Request $request)
    {
        $rules = [
            'period' => 'required|string',
            'team_id' => 'required|string',
            'level' => [
                'required',
                'integer',
                Rule::in([-10, 20, 30, 40]),
            ]
        ];
        $this->validate($request, $rules);
        /**
         * @var User $user
         */
        $user = Auth::user();
        $period = $request->input('period');
        $teamId = $request->input('team_id');
        //$level = $request->input('level');
        /**
         * @var StepService $stepService
         * @var QueryService $queryService
         */
        $stepService = app()->make(StepService::class);
        $queryService = app()->make(QueryService::class);

        $tree = $queryService->query($user, $period, $teamId, false);
        $step = $stepService->getCurrentStep($user, $period, $teamId, $tree);
        $data = [
            'step' => $step,
            'info' => StepService::$steps[$step],
        ];
        return $this->success($data);
    }
}

<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Common\PeriodService;
use App\Services\Task\ArchitectService;
use App\Utils\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommonController extends Controller
{

    /**
     * @api {get} /periods
     * @apiDescription 获取季度下拉框数据
     * @apiGroup Common
     * @apiVersion 1.0.0
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": [
     * "2018Q4",
     * "2018Q3"
     * ]
     * }
     * @param PeriodService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function periods(PeriodService $service)
    {
        $periods = $service->getPeriods();
        return $this->success($periods);
    }

    /**
     * @api {get} /groups
     * @apiDescription 获取售卖渠道的值
     * @apiGroup Common
     * @apiVersion 1.0.0
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": {
     * "direct": "直客"
     * }
     * }
     * @return \Illuminate\Http\JsonResponse
     */
    public function groups()
    {
        /**
         * @var User $user
         * @var ArchitectService $architectService
         */
        $user = Auth::user();
        $architectService = app()->make(ArchitectService::class);
        $data = $architects = [];
        if ($user->isAdmin()) {
            $architects = $architectService->getAdminArchitects();
        }
        if ($user->isOperator()) {
            $architects = $architectService->getOperatorArchitects($user);
        }
        foreach ($architects as $architect) {
            $data[$architect['team_id']] = $architect['name'];
        }
        return $this->success($data);

        /*
        $user = Auth::user();
        if ($user->isAdmin()) {
            $groups = TaskConstant::$groups;
            $ret = [];
            foreach ($groups as $type => $group) {
                $ret[$group['group']] = $group['comment'];
            }
            return $this->success($ret);
        }

        $group = ArchitectService::getUserGroup($user);
        $groupId = TaskConstant::getGroupTargetId($group);
        $groupComment = TaskConstant::getGroupComment($group);
        $groups = [
            $groupId => $groupComment
        ];
        return $this->success($groups);
        */
    }

    /**
     * @api {get} /architects
     * @apiDescription 获取组织架构范围下拉框数据
     * @apiGroup Common
     * @apiVersion 1.0.0
     * @apiParam {String} period 季度
     *
     * @apiSuccessExample {json} 正确返回值:
     * {
     * "code": 0,
     * "msg": "OK",
     * "data": [
     * {
     * "team_id": "FF3BAF22-8642-11E7-B486-ECF4BBC3BE1C",
     * "name": "KA八组",
     * "code": "",
     * "level": 30,
     * "type": 1,
     * "is_owner": 1,
     * "is_operator":0
     * },
     * {
     * "team_id": "A5D59EEB-16A9-11E7-88C5-ECF4BBC3BE1C",
     * "name": "KA三组",
     * "code": "",
     * "level": 20,
     * "type": 1,
     * "is_owner": 1,
     * "is_operator":0
     * },
     * {
     * "team_id": "E5233EAD-8642-11E7-B486-ECF4BBC3BE1C",
     * "name": "KA八组",
     * "code": "",
     * "level": 20,
     * "type": 1,
     * "is_owner": 1,
     * "is_operator":0
     * }
     * ]
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function architects(Request $request)
    {
        /**
         * @var User $user
         * @var ArchitectService $architectService
         */
        $user = Auth::user();

        $architectService = app()->make(ArchitectService::class);
        if ($user->isAdmin()) {
            $architects = $architectService->getAdminArchitects();
            return $this->success($architects);
        }
        if ($user->isOperator()) {
            $architects = $architectService->getOperatorArchitects($user);
            return $this->success($architects);
        }
        $period = $request->input('period');
        $minLevel = $request->input('min_level');
        $isOwner = $request->input('is_owner', null);
        list($begin, $end) = Utils::getQuarterBeginEnd($period);
        $teams = $architectService->getSaleTeams($user->getRtx(), $begin, $end, $minLevel, $isOwner);
        return $this->success($teams);
    }
}

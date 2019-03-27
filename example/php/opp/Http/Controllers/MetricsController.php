<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\MetricsService;
use App\Exceptions\API\NotFound;

class MetricsController extends Controller
{
    /**
     * @api {GET} /metrics 查询商机指标
     * @apiGroup Metrics
     * @apiName GetMetrics
     * @apiParam {String} type 指标名称,WIP丢单率(wip_lose_rate),商机准确率(accuracy_rate),商机前置率(preposition_rate)
     * @apiParam {String} begin 统计指标开始时间，默认上周起始时间
     * @apiParam {String} end 统计指标结束时间，默认上周结束时间
     * @apiUse NotFound
     * @apiSuccessExample 返回的商机指标
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "begin": "2019-03-04 00:00:00",
     *         "end": "2019-03-10 23:59:59",
     *         "wip_lose_rate": 31,
     *         "accuracy_rate": 1.3,
     *         "preposition_rate": 59.3
     *     }
     * }
     *
     * @param Request $request
     * @param MetricsService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, MetricsService $service)
    {
        $type = $request->get('type');
        $method = 'get' . studly_case($type);
        if (!method_exists($service, $method)) {
            throw new NotFound(0, '该指标不存在');
        }
        $begin = Carbon::make($request->get('begin', Carbon::now()->subWeek(1)->startOfWeek()))->startOfDay();
        $end = Carbon::make($request->get('end', Carbon::now()->subWeek(1)->endOfWeek()))->endOfDay();
        $data = [
            'begin' => $begin->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            $type => $service->$method($begin, $end),
        ];
        return $this->success($data);
    }
}

<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Revenue\ProgressService;
use App\Services\Revenue\RevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\API\ValidationFailed;

class ProgressController extends Controller
{
    /**
     * @api {get} /revenue/overview 业绩进度概览数据
     * @apiDescription 业绩进度概览数据
     * @apiGroup Revenue
     * @apiVersion 1.0.0
     * @apiParam {Integer} year
     * @apiParam {Integer} quarter
     * @apiParam {String} sale_id
     * @apiParam {String} team_id
     * @apiParam {Integer} arch_type
     * @apiParam {String} channel_type 渠道类型：direct：直客销售，channel：渠道销售
     * @apiSuccessExample {json} 正确返回值:
     * {"code":0,"msg":"OK","data":{"money":12681,"task":"","forecast_money":13206,"forecast_gap":"","q_opp_ongoing":0,"q_wip":525}}
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overview(Request $request)
    {
        /**
         * @var User $user
         */
        $archType = $request->input('arch_type');
        $user = Auth::user();
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $channelType = $request->input('channel_type');
        /**
         * @var RevenueService $revenueService
         */
        $revenueService = app(RevenueService::class);
        $checkResult = $revenueService->checkProductPrivilege($user, $year, $quarter, $archType, $saleId, $teamId,
            $channelType);

        if (!$checkResult) {
            $message = "没有权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }

        $tree = $revenueService->getProductTree($year, $quarter);
        $overallData = $revenueService->getFlattenSaleOverallDataQuarterly($year, $quarter, $archType, $saleId, $teamId,
            $channelType);
        list($revenueOpp, $task, $forecast) = $overallData;
        $overallData = $revenueService->formatSaleOverallData($revenueOpp, $task, $forecast, $tree, null, $channelType);
        $node = $overallData['records'][0];

        //整体收入、任务执行进度
        $data = [
            'money' => $node['qtd_money'],
            'task' => $node['q_task'],
            'forecast_money' => $node['q_forecast'],
            'forecast_gap' => $node['forecast_gap'],
            'q_opp_ongoing' => $node['q_opp_ongoing'],
            'q_wip' => $node['q_wip'],
        ];


        return $this->success($data);
    }

    /**
     * @api {get} /revenue/progress 销售业绩小组的进度对比
     * @apiDescription 销售业绩小组的进度对比
     * @apiGroup Revenue
     * @apiVersion 1.0.0
     * @apiParam {Integer} year
     * @apiParam {Integer} quarter
     * @apiParam {String} sale_id
     * @apiParam {String} team_id
     * @apiParam {Integer} arch_type
     * @apiParam {String} channel_type 渠道类型：direct：直客销售，channel_industry：渠道销售
     * @apiSuccessExample {json} 正确返回值:
     * {"ret_code":1001,
     * "ret_msg":"ok","data":[{"tb":"","name":"zhezhou(周哲)","order_rate":"","order_rate_fy":"","yoy":0},
     * {"tb":-70,"name":"本土新能源组","order_rate":"","order_rate_fy":90,"yoy":-90},
     * {"tb":-9,"name":"汽车销售华东大区","order_rate":"","order_rate_fy":79,"yoy":-79}]
     * }
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function progress(Request $request)
    {
        /**
         * @var User $user
         */
        $archType = $request->input('arch_type');
        $user = Auth::user();
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $channelType = $request->input('channel_type');

        /**
         * @var $revenueService ProgressService
         */
        $revenueService = app(ProgressService::class);
        $checkResult = $revenueService->checkProductPrivilege($user, $year, $quarter, $archType, $saleId, $teamId,
            $channelType);

        if (!$checkResult) {
            $message = "没有权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }

        $tree = $revenueService->getProductTree($year, $quarter);
        $overallData = $revenueService->getCompareData($year, $quarter, $tree, $archType, $saleId, $teamId,
            $channelType);
        return $this->success($overallData);
    }
}

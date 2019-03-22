<?php

namespace App\Http\Controllers\Revenue;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Exceptions\API\ValidationFailed;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Revenue\ArchitectService;
use App\Services\Revenue\Formatter\OverallFormatter;
use App\Services\Revenue\RevenueService;
use App\Utils\NumberUtil;
use App\Utils\TimerUtil;
use App\Utils\Utils;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RevenueController extends Controller
{
    /**
     * @api {get} /revenue/products 产品维度的业绩
     * @apiDescription 产品维度的业绩
     * @apiGroup Revenue
     * @apiVersion 1.0.0
     * @apiParam {Integer} year 年
     * @apiParam {Integer} quarter 季度
     * @apiParam {String} sale_id
     * @apiParam {String} team_id
     * @apiParam {Integer} arch_type 即为user-roles接口的role type
     * @apiParam {String} channel_type 渠道类型：direct：直客销售，channel_industry：渠道销售
     * @apiSuccessExample {json} 正确返回值=>
     *
     * {"code":0,
     * "msg":"OK",
     * "data":{"records":[
     * {"qtd_money":5811,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":1740,"qtd_normal_money_fy":0,"qtd_business_money":4071,"qtd_business_money_fy":0,"q_wip":555,"q_opp":3595,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":2,"q_opp_ongoing":0,"q_opp_remain":555,"q_opp_order":4151,"q_forecast":6366,
     * "children":[{"product_value":2,"qtd_money":1740,"q_forecast":"-","arch_name":"\u5e38\u89c4","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"normal"},
     * {"product_value":2,"qtd_money":4071,"q_forecast":"-","arch_name":"\u62db\u5546","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"business"}],"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u817e\u8baf\u89c6\u9891","product_raw":"\u817e\u8baf\u89c6\u9891","name":"\u817e\u8baf\u89c6\u9891","arch_name":"\u817e\u8baf\u89c6\u9891","mtype":"all"},
     * {"qtd_money":4704,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":170,"qtd_normal_money_fy":0,"qtd_business_money":4534,"qtd_business_money_fy":0,"q_wip":1244,"q_opp":5650,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":3,"q_opp_ongoing":0,"q_opp_remain":1244,"q_opp_order":4704,"q_forecast":5948,"children":[{"product_value":3,"qtd_money":170,"q_forecast":"-","arch_name":"\u5e38\u89c4","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"normal"},
     * {"product_value":3,"qtd_money":4534,"q_forecast":"-","arch_name":"\u62db\u5546","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"business"}],"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u65b0\u95fbAPP","product_raw":"\u65b0\u95fbAPP","name":"\u65b0\u95fbAPP","arch_name":"\u65b0\u95fbAPP","mtype":"all"},
     * {"qtd_money":1250,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":1250,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":"","sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":4,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":1250,"children":[{"product_value":4,"qtd_money":1250,"q_forecast":"-","arch_name":"\u5e38\u89c4","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"normal"},
     * {"product_value":4,"qtd_money":0,"q_forecast":"-","arch_name":"\u62db\u5546","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"business"}],"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u5408\u7ea6\u670b\u53cb\u5708","product_raw":"\u5408\u7ea6\u670b\u53cb\u5708","name":"\u5408\u7ea6\u670b\u53cb\u5708","arch_name":"\u5408\u7ea6\u670b\u53cb\u5708","mtype":"all"},
     * {"qtd_money":916,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":32,"qtd_normal_money_fy":0,"qtd_business_money":885,"qtd_business_money_fy":0,"q_wip":0,"q_opp":"","sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":5,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":916,"children":[{"product_value":5,"qtd_money":32,"q_forecast":"-","arch_name":"\u5e38\u89c4","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"normal"},
     * {"product_value":5,"qtd_money":885,"q_forecast":"-","arch_name":"\u62db\u5546","qtd_finish_rate":"-","q_money_yoy":"-","mtype":"business"}],"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":"","director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u5176\u4ed6\u54c1\u724c\u6536\u5165","product_raw":"\u5176\u4ed6\u54c1\u724c\u6536\u5165","name":"\u5176\u4ed6\u54c1\u724c\u6536\u5165","arch_name":"\u5176\u4ed6\u54c1\u724c\u6536\u5165","mtype":"all"},
     * {"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":6,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6548\u679c","product_raw":"\u6548\u679c","name":"\u6548\u679c\u6574\u4f53","arch_name":"\u6548\u679c\u6574\u4f53","mtype":"all",
     * "children":[{"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":7,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u5e7f\u70b9\u901a","product_raw":"\u5e7f\u70b9\u901a","name":"\u5e7f\u70b9\u901a","arch_name":"\u5e7f\u70b9\u901a","mtype":"all"},
     * {"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":8,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u516c\u4f17\u53f7","product_raw":"\u516c\u4f17\u53f7","name":"\u516c\u4f17\u53f7","arch_name":"\u516c\u4f17\u53f7","mtype":"all"},
     * {"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":9,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u7ade\u4ef7\u670b\u53cb\u5708","product_raw":"\u7ade\u4ef7\u670b\u53cb\u5708","name":"\u7ade\u4ef7\u670b\u53cb\u5708","arch_name":"\u7ade\u4ef7\u670b\u53cb\u5708","mtype":"all"}]},
     * {"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":20,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u5176\u4ed6","product_raw":"\u5176\u4ed6","name":"\u5176\u4ed6","arch_name":"\u5176\u4ed6","mtype":"all",
     * "children":[{"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":21,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u817e\u8baf\u89c6\u9891-\u975e\u5e7f\u544a-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","product_raw":"\u817e\u8baf\u89c6\u9891-\u975e\u5e7f\u544a-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","name":"\u817e\u8baf\u89c6\u9891-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","arch_name":"\u817e\u8baf\u89c6\u9891-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","mtype":"all"},{"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":22,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u817e\u8baf\u65b0\u95fb-\u975e\u5e7f\u544a-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","product_raw":"\u817e\u8baf\u65b0\u95fb-\u975e\u5e7f\u544a-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","name":"\u817e\u8baf\u65b0\u95fb-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","arch_name":"\u817e\u8baf\u65b0\u95fb-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","mtype":"all"},
     * {"qtd_money":0,"qtd_money_fq":0,"qtd_money_fy":0,"q_money_fq":0,"q_money_fy":0,"qtd_normal_money":0,"qtd_normal_money_fy":0,"qtd_business_money":0,"qtd_business_money_fy":0,"q_wip":0,"q_opp":0,"sale_id":"660ABF52-1DAA-11E6-98ED-6CAE8B22C292","team_id":"A256DFA9-267C-DE11-B2E8-001D096CF989","product_value":23,"q_opp_ongoing":0,"q_opp_remain":0,"q_opp_order":0,"q_forecast":0,"director_fore_money":0,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":"","yoy":0,"q_money_yoy":"","q_money_qoq":"","director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u5176\u4ed6\u975e\u5e7f\u544a\u6536\u5165-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","product_raw":"\u5176\u4ed6\u975e\u5e7f\u544a\u6536\u5165-\u4e0d\u8ba1\u5e73\u53f0\u4e1a\u7ee9","name":"\u5176\u4ed6-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","arch_name":"\u5176\u4ed6-\u975e\u5e7f\u544a-\u4e0d\u8bb0\u4e1a\u7ee9","mtype":"all"}]}],
     * "date":{"data_date":"2018-12-02","period":"2018Q4"}}}
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request)
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
        $channelType = $request->input('channel_type', 'direct');
        $mobileSource = $request->input('m_src');

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
        $data = $revenueService->formatSaleOverallData($revenueOpp, $task, $forecast, $tree, $mobileSource,
            $channelType);
        $data['date'] = [
            "data_date" => $revenueService->getRevenueUpdateTime($year, $quarter, $channelType),
            "period" => "{$year}Q{$quarter}"
        ];

        return $this->success($data);
    }


    /**
     * @api {get} /revenue/drill-down  用户下级的业绩
     * @apiDescription  用户下级的业绩
     * @apiGroup Revenue
     * @apiVersion 1.0.0
     * @apiParam {Integer} year
     * @apiParam {Integer} quarter
     * @apiParam {String} sale_id
     * @apiParam {String} team_id
     * @apiParam {Integer} arch_type
     * @apiParam {String} drill_id
     * @apiParam {String} drill_pid
     * @apiParam {Integer} arch_type_drill
     * @apiParam {String} channel_type 渠道类型：direct：直客销售，channel_industry：渠道销售
     * @apiSuccessExample {json} 正确返回值=>
     * {"code":0,"msg":"OK","data":{"records":[
     * {"qtd_money":2455772,"qtd_money_fq":6625232,"qtd_money_fy":4023215,"q_money_fq":8505702,"q_money_fy":5737814,"qtd_normal_money":1174407,"qtd_normal_money_fy":0,"qtd_business_money":1281365,"qtd_business_money_fy":0,"q_wip":3742742,"q_opp":7447204,"product_value":100,"q_opp_ongoing":1438205,"q_opp_remain":6077971,"q_opp_order":1586567,"q_forecast":6198476,"q_task":"","director_fore_money":2766620,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":70,"yoy":-70,"q_money_yoy":-39,"q_money_qoq":-63,"director_fore_money_lost":-2766620,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","name":"\u54c1\u724c\u6548\u679c\u6574\u4f53+\u5176\u4ed6","arch_name":"\u5168\u90e8","mtype":"all","arch_id":"","arch_type":0},{"arch_name":"KA\u4e1a\u52a1\u90e8","area_name":"KA\u4e1a\u52a1\u90e8","q_task":0,"qtd_money":450964,"qtd_finish_rate":"","forecast_gap":0,"sort":"1","children":[{"qtd_money":47784,"qtd_money_fq":76983,"qtd_money_fy":263521,"q_money_fq":95224,"q_money_fy":276030,"qtd_normal_money":36002,"qtd_normal_money_fy":0,"qtd_business_money":11782,"qtd_business_money_fy":0,"q_wip":115385,"q_opp":330706,"area_id":"910526A5-16A4-11E7-88C5-ECF4BBC3BE1C","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":129905,"q_opp_remain":285891,"q_opp_order":44815,"q_forecast":163169,"q_task":"","director_fore_money":330000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":95,"yoy":0,"q_money_yoy":-82,"q_money_qoq":-38,"director_fore_money_lost":-330000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"910526A5-16A4-11E7-88C5-ECF4BBC3BE1C","arch_guid":"F4B29908-5240-F99D-4E21-1CEF357FA766","arch_name":"KA\u4e00\u7ec4-\u8303\u5955\u747e","name":"KA\u4e00\u7ec4","arch_name_raw":"KA\u4e00\u7ec4","arch_type":1,"sort":1},{"qtd_money":72704,"qtd_money_fq":131887,"qtd_money_fy":81936,"q_money_fq":177641,"q_money_fy":102304,"qtd_normal_money":47276,"qtd_normal_money_fy":0,"qtd_business_money":25428,"qtd_business_money_fy":0,"q_wip":1269148,"q_opp":1362357,"area_id":"0A6B6218-BC5F-18FF-F914-1FBFB00AA115","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":41637,"q_opp_remain":1311335,"q_opp_order":52630,"q_forecast":1341851,"q_task":"","director_fore_money":120000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":80,"yoy":0,"q_money_yoy":-11,"q_money_qoq":-45,"director_fore_money_lost":-120000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"0A6B6218-BC5F-18FF-F914-1FBFB00AA115","arch_guid":"1C2064D5-4B66-FC81-5943-2F2EAACE99F8","arch_name":"KA\u4e8c\u7ec4-\u7fc1\u8bd7\u96c5","name":"KA\u4e8c\u7ec4","arch_name_raw":"KA\u4e8c\u7ec4","arch_type":1,"sort":2},{"qtd_money":20498,"qtd_money_fq":123006,"qtd_money_fy":40291,"q_money_fq":129369,"q_money_fy":55414,"qtd_normal_money":14498,"qtd_normal_money_fy":0,"qtd_business_money":6000,"qtd_business_money_fy":0,"q_wip":34379,"q_opp":56875,"area_id":"A739EBDD-1F3A-5FDA-19CE-C3822D097668","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":0,"q_opp_remain":34379,"q_opp_order":22495,"q_forecast":54878,"director_fore_money":7000,"forecast_gap":"","q_task":"","qtd_finish_rate":"","qtd_finish_rate_fy":73,"yoy":0,"q_money_yoy":-49,"q_money_qoq":-83,"director_fore_money_lost":-7000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"A739EBDD-1F3A-5FDA-19CE-C3822D097668","arch_guid":"DAE89AEE-2E32-D786-F04B-DE08F39108B8","arch_name":"KA\u4e94\u7ec4","name":"KA\u4e94\u7ec4","arch_name_raw":"KA\u4e94\u7ec4","arch_type":1,"sort":3},
     * {"qtd_money":37002,"qtd_money_fq":109691,"qtd_money_fy":37661,"q_money_fq":120927,"q_money_fy":77134,"qtd_normal_money":18012,"qtd_normal_money_fy":0,"qtd_business_money":18989,"qtd_business_money_fy":0,"q_wip":30465,"q_opp":58722,"area_id":"41A19572-8645-11E7-B486-ECF4BBC3BE1C","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":0,"q_opp_remain":30465,"q_opp_order":30083,"q_forecast":67467,"q_task":"","director_fore_money":97000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":49,"yoy":0,"q_money_yoy":-2,"q_money_qoq":-66,"director_fore_money_lost":-97000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"41A19572-8645-11E7-B486-ECF4BBC3BE1C","arch_guid":"2CD9CE6B-5C5F-B313-D5AC-D6C892BE45C3","arch_name":"KA\u4e5d\u7ec4-\u8303\u5955\u747e","name":"KA\u4e5d\u7ec4","arch_name_raw":"KA\u4e5d\u7ec4","arch_type":1,"sort":4},{"qtd_money":41031,"qtd_money_fq":50746,"qtd_money_fy":57232,"q_money_fq":63117,"q_money_fy":81308,"qtd_normal_money":21951,"qtd_normal_money_fy":0,"qtd_business_money":19080,"qtd_business_money_fy":0,"q_wip":3532,"q_opp":40778,"area_id":"7EBA44D2-16A9-11E7-88C5-ECF4BBC3BE1C","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":0,"q_opp_remain":3532,"q_opp_order":38020,"q_forecast":44564,"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":70,"yoy":0,"q_money_yoy":-28,"q_money_qoq":-19,"director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"7EBA44D2-16A9-11E7-88C5-ECF4BBC3BE1C","arch_guid":"6A5C5AAF-29AA-9A8C-5520-A3ECE2900872","arch_name":"KA\u7b2c\u4e00\u4e1a\u52a1\u7fa4\u7ec4-\u8303\u5955\u747e","name":"KA\u7b2c\u4e00\u4e1a\u52a1\u7fa4\u7ec4","arch_name_raw":"KA\u7b2c\u4e00\u4e1a\u52a1\u7fa4\u7ec4","arch_type":1,"sort":5},{"qtd_money":96548,"qtd_money_fq":148534,"qtd_money_fy":137812,"q_money_fq":205334,"q_money_fy":205824,"qtd_normal_money":75803,"qtd_normal_money_fy":0,"qtd_business_money":20746,"qtd_business_money_fy":0,"q_wip":63825,"q_opp":119700,"area_id":"859F3FED-16AC-11E7-88C5-ECF4BBC3BE1C","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":33700,"q_opp_remain":100025,"q_opp_order":20375,"q_forecast":160373,"q_task":"","director_fore_money":70000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":67,"yoy":0,"q_money_yoy":-30,"q_money_qoq":-35,"director_fore_money_lost":-70000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"859F3FED-16AC-11E7-88C5-ECF4BBC3BE1C","arch_guid":"B472CD8C-1584-045C-571C-71F4E91663C2","arch_name":"KA\u7b2c\u4e8c\u4e1a\u52a1\u7fa4\u7ec4-\u8303\u5955\u747e","name":"KA\u7b2c\u4e8c\u4e1a\u52a1\u7fa4\u7ec4","arch_name_raw":"KA\u7b2c\u4e8c\u4e1a\u52a1\u7fa4\u7ec4","arch_type":1,"sort":6},{"qtd_money":113353,"qtd_money_fq":242674,"qtd_money_fy":101394,"q_money_fq":326960,"q_money_fy":192290,"qtd_normal_money":42893,"qtd_normal_money_fy":0,"qtd_business_money":70460,"qtd_business_money_fy":0,"q_wip":60880,"q_opp":104864,"area_id":"C9E5B547-8640-11E7-B486-ECF4BBC3BE1C","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":10000,"q_opp_remain":70880,"q_opp_order":38137,"q_forecast":174232,"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":53,"yoy":0,"q_money_yoy":12,"q_money_qoq":-53,"director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"C9E5B547-8640-11E7-B486-ECF4BBC3BE1C","arch_guid":"78268A4F-D38A-A33C-FABA-8AF738ADEFB9","arch_name":"KA\u7b2c\u4e09\u4e1a\u52a1\u7fa4\u7ec4-\u8303\u5955\u747e","name":"KA\u7b2c\u4e09\u4e1a\u52a1\u7fa4\u7ec4","arch_name_raw":"KA\u7b2c\u4e09\u4e1a\u52a1\u7fa4\u7ec4","arch_type":1,"sort":7},
     * {"qtd_money":22044,"qtd_money_fq":20096,"qtd_money_fy":7400,"q_money_fq":30248,"q_money_fy":12231,"qtd_normal_money":3421,"qtd_normal_money_fy":0,"qtd_business_money":18623,"qtd_business_money_fy":0,"q_wip":9,"q_opp":15250,"area_id":"C7A4D32C-9FBA-982B-479D-16A9B55DD6AD","department_id":"6CC5BE11-FBAF-F34D-CA01-60A70FE4E400","product_value":100,"q_opp_ongoing":0,"q_opp_remain":9,"q_opp_order":15641,"q_forecast":22053,"q_task":"","director_fore_money":35000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":61,"yoy":0,"q_money_yoy":198,"q_money_qoq":10,"director_fore_money_lost":-35000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"C7A4D32C-9FBA-982B-479D-16A9B55DD6AD","arch_guid":"9E326E81-66A5-6631-04D3-126D08ADB3AB","arch_name":"\u96f6\u552e\u7ec4-\u8303\u5955\u747e","name":"\u96f6\u552e\u7ec4","arch_name_raw":"\u96f6\u552e\u7ec4","arch_type":1,"sort":8}],"q_opp_ongoing":215242,"q_wip":1577623,"q_forecast":2028587,"qtd_money_fq":903617,"qtd_money_fy":727247,"q_money_fq":1148820,"q_money_fy":1002535,"qtd_normal_money":259856,"qtd_normal_money_fy":0,"qtd_business_money":191108,"qtd_business_money_fy":0,"q_opp":2089252},{"arch_name":"\u884c\u4e1a\u4e1a\u52a1\u90e8","area_name":"\u884c\u4e1a\u4e1a\u52a1\u90e8","q_task":0,"qtd_money":1890250,"qtd_finish_rate":"","forecast_gap":0,"sort":"2",
     * "children":[{"qtd_money":633457,"qtd_money_fq":1451689,"qtd_money_fy":718306,"q_money_fq":1673803,"q_money_fy":974212,"qtd_normal_money":216568,"qtd_normal_money_fy":0,"qtd_business_money":416889,"qtd_business_money_fy":0,"q_wip":1234587,"q_opp":2586102,"area_id":"10DB0A05-16A7-11E7-88C5-ECF4BBC3BE1C","department_id":"C97F0221-7FB2-4C78-4B5B-A12D2F2CE27C","product_value":100,"q_opp_ongoing":857170,"q_opp_remain":2167241,"q_opp_order":493459,"q_forecast":1868044,"q_task":"","director_fore_money":0,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":74,"yoy":0,"q_money_yoy":-12,"q_money_qoq":-56,"director_fore_money_lost":0,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"10DB0A05-16A7-11E7-88C5-ECF4BBC3BE1C","arch_guid":"DD223423-D786-C6D3-666E-61DF6B24676A","arch_name":"\u5feb\u6d88\u9500\u552e\u4e2d\u5fc3-\u65bd\u8d5b\u98de","name":"\u5feb\u6d88\u9500\u552e\u4e2d\u5fc3","arch_name_raw":"\u5feb\u6d88\u9500\u552e\u4e2d\u5fc3","arch_type":1,"sort":9},{"qtd_money":472760,"qtd_money_fq":548933,"qtd_money_fy":506614,"q_money_fq":707574,"q_money_fy":660466,"qtd_normal_money":241258,"qtd_normal_money_fy":0,"qtd_business_money":231502,"qtd_business_money_fy":0,"q_wip":173750,"q_opp":474713,"area_id":"D0917815-BE93-E411-BC79-001D096CF989","department_id":"C97F0221-7FB2-4C78-4B5B-A12D2F2CE27C","product_value":100,"q_opp_ongoing":51262,"q_opp_remain":233517,"q_opp_order":275612,"q_forecast":646472,"q_task":"","director_fore_money":582820,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":77,"yoy":0,"q_money_yoy":-7,"q_money_qoq":-14,"director_fore_money_lost":-582820,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"D0917815-BE93-E411-BC79-001D096CF989","arch_guid":"A7C88933-5EF7-FFD5-C54D-2E1401F8A95D","arch_name":"\u6c7d\u8f66\u9500\u552e\u4e2d\u5fc3-\u738b\u79cb\u51e4","name":"\u6c7d\u8f66\u9500\u552e\u4e2d\u5fc3","arch_name_raw":"\u6c7d\u8f66\u9500\u552e\u4e2d\u5fc3","arch_type":1,"sort":10},{"qtd_money":371755,"qtd_money_fq":808591,"qtd_money_fy":668976,"q_money_fq":1019752,"q_money_fy":1019351,"qtd_normal_money":227279,"qtd_normal_money_fy":0,"qtd_business_money":144476,"qtd_business_money_fy":0,"q_wip":456067,"q_opp":952733,"area_id":"D85C8A11-16A4-11E7-88C5-ECF4BBC3BE1C","department_id":"C97F0221-7FB2-4C78-4B5B-A12D2F2CE27C","product_value":100,"q_opp_ongoing":163330,"q_opp_remain":773339,"q_opp_order":200455,"q_forecast":827822,"q_task":"","director_fore_money":819800,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":66,"yoy":0,"q_money_yoy":-44,"q_money_qoq":-54,"director_fore_money_lost":-819800,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"D85C8A11-16A4-11E7-88C5-ECF4BBC3BE1C","arch_guid":"B60CE3EA-522D-A0A3-0A0B-EDBAA7E83BDE","arch_name":"\u6d88\u7535IT\u91d1\u878d\u9500\u552e\u4e2d\u5fc3-\u5f20\u83c1","name":"\u6d88\u7535IT\u91d1\u878d\u9500\u552e\u4e2d\u5fc3","arch_name_raw":"\u6d88\u7535IT\u91d1\u878d\u9500\u552e\u4e2d\u5fc3","arch_type":1,"sort":11},{"qtd_money":412278,"qtd_money_fq":779693,"qtd_money_fy":677415,"q_money_fq":958030,"q_money_fy":881536,"qtd_normal_money":137141,"qtd_normal_money_fy":0,"qtd_business_money":275137,"qtd_business_money_fy":0,"q_wip":255809,"q_opp":1260738,"area_id":"3926F041-16A9-11E7-88C5-ECF4BBC3BE1C","department_id":"C97F0221-7FB2-4C78-4B5B-A12D2F2CE27C","product_value":100,"q_opp_ongoing":141298,"q_opp_remain":990328,"q_opp_order":347519,"q_forecast":668086,"q_task":"","director_fore_money":705000,"forecast_gap":"","qtd_finish_rate":"","qtd_finish_rate_fy":77,"yoy":0,"q_money_yoy":-39,"q_money_qoq":-47,"director_fore_money_lost":-705000,"director_fore_money_finish_rate":"","forecast_finish_rate":"","q_opp_finish_rate":"","product":"\u6574\u4f53","product_raw":"\u6574\u4f53","arch_id":"3926F041-16A9-11E7-88C5-ECF4BBC3BE1C","arch_guid":"8D547E27-38A2-F471-800C-ACA16332D0F9","arch_name":"\u7f51\u670d\u7f51\u6e38\u9500\u552e\u4e2d\u5fc3-\u9648\u4e16\u6d2a","name":"\u7f51\u670d\u7f51\u6e38\u9500\u552e\u4e2d\u5fc3","arch_name_raw":"\u7f51\u670d\u7f51\u6e38\u9500\u552e\u4e2d\u5fc3","arch_type":1,"sort":12}],"q_opp_ongoing":1213060,"q_wip":2120213,"q_forecast":4010424,"qtd_money_fq":3588906,"qtd_money_fy":2571311,"q_money_fq":4359159,"q_money_fy":3535565,"qtd_normal_money":822246,"qtd_normal_money_fy":0,"qtd_business_money":1068004,"qtd_business_money_fy":0,"q_opp":5274286}],
     * "top":[{"name":"\u884c\u4e1a\u4e1a\u52a1\u90e8","money":1890250,"ratio":81},
     * {"name":"KA\u4e1a\u52a1\u90e8","money":450964,"ratio":19}]}}
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drillDown(Request $request)
    {
        TimerUtil::start('drill-down start');
        $archType = (int)$request->input('arch_type_drill');
        if ($this->isLeaderLevel($archType)) {
            $result = $this->drillAboveSale($request);
        } else {
            $result = $this->drillUnderSale($request);
        }

        if (isset($result['records_without_total_other'])) {
            $result['top'] = $this->getTopData($result['records_without_total_other'], 5, 'arch_name');
            unset($result['records_without_total_other']);
        }
        TimerUtil::log('drill-down end');
        $times = TimerUtil::get();
        $result['timer'] = $times;
        return $this->success($result);
    }

    private function getDrillDownValidateRules()
    {
        $archTypeArr = [
            RevenueConst::ARCH_TYPE_NATION,
            RevenueConst::ARCH_TYPE_AREA,
            RevenueConst::ARCH_TYPE_DIRECTOR,
            RevenueConst::ARCH_TYPE_TEAM,
            RevenueConst::ARCH_TYPE_SALE,
            RevenueConst::ARCH_TYPE_SHORT,
            RevenueConst::ARCH_TYPE_CLIENT,
            RevenueConst::ARCH_TYPE_DEPT,
        ];
        $mTypeArr = ['all', RevenueConst::INCOME_TYPE_CG_M, RevenueConst::INCOME_TYPE_ZS_M];
        return [
            'year' => 'required|integer|min:2016|max:2099',
            'quarter' => 'required|integer|min:1|max:4',
            'channel_type' => [
                'sometimes',
                Rule::in([ProjectConst::SALE_CHANNEL_TYPE_DIRECT, ProjectConst::SALE_CHANNEL_TYPE_CHANNEL]),
            ],
            'team_id' => 'required|string|max:36',
            'sale_id' => 'required|string|max:36',
            'arch_type' => [
                'required',
                Rule::in($archTypeArr),
            ],
            'arch_type_drill' => [
                'sometimes',
                Rule::in($archTypeArr),
            ],
            'drill_id' => 'sometimes|string|max:36',
            'product' => 'sometimes|integer',
            'mtype' => [
                'sometimes',
                'string',
                Rule::in($mTypeArr)
            ],
        ];
    }

    protected function drillAboveSale(Request $request)
    {
        $rules = $this->getDrillDownValidateRules();
        $this->validate($request, $rules);

        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $roleType = (int)$request->input('arch_type');
        $product = $request->input('product') ? (int)$request->input('product') : RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER;
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $mtype = empty($request->input('mtype')) ? 'all' : $request->input('mtype');
        $mpage = $request->input('mpage');
        $incomeType = $request->input('income_type'); //招商，常规字段区分

        //往pc端的格式转
        if (empty($incomeType)) {
            $mapTypeToIncomeType = [
                'all' => null,
                'normal' => RevenueConst::INCOME_TYPE_CG,
                'business' => RevenueConst::INCOME_TYPE_ZS,
            ];
            if (!empty($mtype)) {
                $incomeType = $mapTypeToIncomeType[$mtype] ?? null;
            }
        }
        $archType = empty($request->input('arch_type_drill')) ? $roleType : (int)$request->input('arch_type_drill'); //下钻的层级
        $drillId = $request->input('drill_id'); //下钻时传入的该行的team_id
        $channelType = empty($request->input('channel_type')) ? 'direct' : $request->input('channel_type');
        $sortBy = $request->input('sort_by');
        $sortType = $request->input('sort_type');

        /**
         * @var RevenueService $revenueService
         */
        $revenueService = app()->make(RevenueService::class);
        /**
         * 处理下钻的权限判断
         * 1. 上层用户下钻的时候必须传入drill_id
         * 2. 默认登录的时候drill_id传空
         *    a. 如果是管理员权限登录，drill_id还是为空
         *    b. 如果是其它权限用户登录，drill_id默认为登录用户所属的架构id
         */
        if (!empty($drillId) && !$revenueService->checkRevenuePrivilege($drillId, $teamId, $year, $quarter)) {
            //判断下钻的层级是否是登录者的下属层级
            throw new ValidationFailed('无权限查看或者操作');
        }
        if (empty($drillId)) {
            $drillId = $teamId;
        }
        $fmt = "year: %d, q: %d, drill: %s, archType: %d, sale_id: %s, product: %d, income: %d, channel: %s. ";
        $result = [];
        $records = $revenueService->getDrillArchRevenues($year, $quarter, $drillId, $archType, $saleId, $product,
            $incomeType, $channelType);
        $records = $this->makeSort($year, $quarter, $archType, $records, $sortBy, $sortType);
        TimerUtil::log(sprintf($fmt . "完成", $year, $quarter, $drillId, $archType, $saleId, $product, $incomeType,
            $channelType));
        if ($archType == RevenueConst::ARCH_TYPE_NATION) {
            //全国层级的时候特殊处理逻辑
            $childRecords = $this->processForNationRevenue($records, $year, $quarter, $saleId, $product, $incomeType,
                $channelType, $sortBy, $sortType);
            foreach ($records as &$record) {
                if (array_key_exists($record['arch_id'], $childRecords)) {
                    $record['children'] = $childRecords[$record['arch_id']];
                }
            }
            unset($record);
        }
        if ($archType == RevenueConst::ARCH_TYPE_DEPT && $channelType == 'channel') {
            //渠道获取下级片区业绩的时候，过滤 渠道拓新业务中心
            //72134738-5983-11E8-AFD7-F02FA73BC80D
            $tmp = [];
            foreach ($records as $row) {
                if ($row['arch_id'] != '72134738-5983-11E8-AFD7-F02FA73BC80D' && !\in_array($row['arch_id'],
                        ArchitectConstant::$smbDirectAreas)) {
                    $tmp[] = $row;
                }
            }
            $records = $tmp;
        }

        if ($archType == RevenueConst::ARCH_TYPE_DEPT && $channelType == 'direct') {
            $tmp = [];
            foreach ($records as $row) {
                if (!\in_array($row['arch_id'],
                    ArchitectConstant::$smbChannelAreas)) {
                    $tmp[] = $row;
                }
            }
            $records = $tmp;
        }

        //获取产品维度的数据
        $tree = $revenueService->getProductTree($year, $quarter);
        $overallData = $revenueService->getFlattenSaleOverallDataQuarterly($year, $quarter, $archType, $saleId,
            $drillId,
            $channelType);
        TimerUtil::log(sprintf($fmt . "获取产品维度业绩完成", $year, $quarter, $drillId, $archType,
            $saleId,
            $product, $incomeType,
            $channelType));
        list($revenueOpp, $task, $forecast) = $overallData;
        $ret = $revenueService->formatSaleOverallData($revenueOpp, $task, $forecast, $tree, true, $channelType);
        TimerUtil::log(sprintf($fmt . "产品业绩格式化完成", $year, $quarter, $drillId, $archType,
            $saleId,
            $product, $incomeType,
            $channelType));
        //根据product过滤产品的业绩
        if ($product == RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER) {
            $products = $ret['records'];
        } else {
            $products = OverallFormatter::getFlattenOverallData();
            $tmpProducts = [];
            foreach ($products as $row) {
                if ($row['product_value'] != $product) {
                    continue;
                }
                if (empty($incomeType)) {
                    $tmpProducts[] = $row;
                    continue;
                }
                if (array_key_exists('children', $row)) {
                    foreach ($row['children'] as $child) {
                        if ($child['income_type'] == $incomeType) {
                            $tmpProducts[] = $child;
                        }
                    }
                }
            }
            $products = $tmpProducts;
        }
        $this->addProductsTreeArchName($products);
        $result['product_list'] = $products;

        if ($mpage == 'overview') {
            $data = $revenueService->formatSaleOverallData($revenueOpp, $task, $forecast, $tree, false, $channelType);
            $overall = $data['records'][0];
            $result['records_without_total_other'] = $records;
            if ($archType == RevenueConst::ARCH_TYPE_NATION && $channelType == 'channel' && count($records) == 1) {
                //渠道的话，展示片区的占比数据
                $recordsWithoutTotal = array_key_exists(0, $records) && array_key_exists('children',
                    $records[0]) ? $records[0]['children'] : [];
                $result['records_without_total_other'] = $recordsWithoutTotal;
            }
            $overall['mtype'] = 'all';
            $overall['arch_name'] = '全部';
            $overall['arch_type'] = $roleType;
            unset($overall['children']);
            $records = array_prepend($records, $overall);

            TimerUtil::log(sprintf($fmt . "overview业绩格式化完成",
                $year,
                $quarter,
                $drillId,
                $archType,
                $saleId,
                $product, $incomeType,
                $channelType));
        }
        $result['records'] = $records;
        return $result;
    }

    protected function drillUnderSale(Request $request)
    {
        $rules = $this->getDrillDownValidateRules();
        $this->validate($request, $rules);

        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $roleType = (int)$request->input('arch_type');
        $product = $request->input('product') ? (int)$request->input('product') : RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER;
        $saleId = $request->input('drill_id');
        $teamId = $request->input('drill_pid');
        $shortId = $request->input('short_id');
        $archType = (int)$request->input('arch_type_drill');
        $mpage = $request->input('mpage');
        $mtype = $request->input('mtype');
        $incomeType = $request->input('income_type'); //招商，常规字段区分
        $channelType = empty($request->input('channel_type')) ? 'direct' : $request->input('channel_type');
        $sortBy = $request->input('sort_by');
        $sortType = $request->input('sort_type');

        //往pc端的格式转
        if (empty($incomeType)) {
            $mapTypeToIncomeType = [
                'all' => null,
                'normal' => RevenueConst::INCOME_TYPE_CG,
                'business' => RevenueConst::INCOME_TYPE_ZS,
            ];
            if (!empty($mtype)) {
                $incomeType = $mapTypeToIncomeType[$mtype];
            }
        }
        /**
         * @var ArchitectService $architectService
         * @var RevenueService $revenueService
         */
        $revenueService = app()->make(RevenueService::class);

        $clientsRevenue = $revenueService->getShortsClientsDataQuarterly(
            $saleId, $teamId, $product, $year, $quarter, $channelType);
        //获取的给定产品的分客户的业绩数据
        $revenueData = isset($clientsRevenue[$product]) ? $clientsRevenue[$product] : [];
        $clientData = $revenueService->formatShortsClientsDataQuarterly(
            $revenueData, $incomeType, $year, $quarter, $shortId, $channelType);

        $data = [];
        //补充移动端字段
        if (!empty($shortId)) {
            //archType=6
            foreach ($clientData['records'] as $key => $item) {
                if ($shortId != $item['short_id']) {
                    continue;
                }
                $data = $item['children'];
                foreach ($data as &$clientInfo) {
                    $clientInfo['arch_name'] = $clientInfo['client_name'];
                }
            }
        } else {
            //archType=5
            foreach ($clientData['records'] as $key => $item) {
                $item['arch_name'] = $item['short_name'];
                unset($item['children']);
                $data[] = $item;
            }
        }
        //过滤收入类型
        foreach ($data as &$row) {
            switch ($incomeType) {
                case RevenueConst::INCOME_TYPE_CG:
                    $row['qtd_money'] = $row['qtd_normal_money'];
                    break;
                case RevenueConst::INCOME_TYPE_ZS:
                    $row['qtd_money'] = $row['qtd_business_money'];
                    break;
                default:
            }
        }
        unset($row);
        //排序
        $data = $this->makeSort($year, $quarter, $archType, $data, $sortBy, $sortType);
        $result['records'] = $data;

        $products = $revenueService->createProductList(
            $clientsRevenue,
            $year,
            $quarter,
            $archType,
            $product,
            $saleId,
            $teamId,
            $shortId,
            $channelType
        );

        if ($product != RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER) {
            $tmpProducts = [];
            foreach ($products as $row) {
                if ($row['product_value'] != $product) {
                    continue;
                }
                if (empty($incomeType)) {
                    $tmpProducts[] = $row;
                    continue;
                }
                if (array_key_exists('children', $row)) {
                    foreach ($row['children'] as $child) {
                        if ($child['income_type'] == $incomeType) {
                            $tmpProducts[] = $child;
                        }
                    }
                }
            }
            $products = $tmpProducts;
        }
        $this->addProductsTreeArchName($products);
        $result['product_list'] = $products;

        if ($mpage == 'overview') {
            $tree = $revenueService->getProductTree($year, $quarter);
            $overallData = $revenueService->getFlattenSaleOverallDataQuarterly($year, $quarter, $archType, $saleId,
                $teamId,
                $channelType);
            list($revenueOpp, $task, $forecast) = $overallData;
            $data = $revenueService->formatSaleOverallData($revenueOpp, $task, $forecast, $tree, false, $channelType);
            $overall = $data['records'][0];
            $result['records_without_total_other'] = $result['records'];
            $overall['mtype'] = 'all';
            $overall['arch_name'] = '全部';
            unset($overall['children']);
            $result['records'] = $this->addTotalAndOther($overall, $result['records']);
        }
        return $result;
    }

    protected function addTotalAndOther($overall, $records)
    {
        if (count($records) <= 5) {
            $data = array_prepend($records, $overall);
            return $data;
        }
        $data[] = $overall;
        $other = $overall;
        $other['director_fore_money'] = isset($other['director_fore_money']) ? (int)$other['director_fore_money'] : 0;
        $other['q_forecast'] = isset($other['q_forecast']) ? (int)$other['q_forecast'] : 0;
        $other['q_money_fq'] = isset($other['q_money_fq']) ? (int)$other['q_money_fq'] : 0;
        $other['q_money_fy'] = isset($other['q_money_fy']) ? (int)$other['q_money_fy'] : 0;
        $other['q_opp'] = isset($other['q_opp']) ? (int)$other['q_opp'] : 0;
        $other['q_opp_ongoing'] = isset($other['q_opp_ongoing']) ? (int)$other['q_opp_ongoing'] : 0;
        $other['q_opp_order'] = isset($other['q_opp_order']) ? (int)$other['q_opp_order'] : 0;
        $other['q_opp_remain'] = isset($other['q_opp_remain']) ? (int)$other['q_opp_remain'] : 0;
        $other['q_task'] = isset($other['q_task']) ? (int)$other['q_task'] : 0;
        $other['q_wip'] = isset($other['q_wip']) ? (int)$other['q_wip'] : 0;
        $other['qtd_business_money'] = isset($other['qtd_business_money']) ? (int)$other['qtd_business_money'] : 0;
        $other['qtd_business_money_fy'] = isset($other['qtd_business_money_fy']) ? (int)$other['qtd_business_money_fy'] : 0;
        $other['qtd_money'] = isset($other['qtd_money']) ? (int)$other['qtd_money'] : 0;
        $other['qtd_money_fq'] = isset($other['qtd_money_fq']) ? (int)$other['qtd_money_fq'] : 0;
        $other['qtd_money_fy'] = isset($other['qtd_money_fy']) ? (int)$other['qtd_money_fy'] : 0;
        $other['qtd_normal_money'] = isset($other['qtd_normal_money']) ? (int)$other['qtd_normal_money'] : 0;
        $other['qtd_normal_money_fy'] = isset($other['qtd_normal_money_fy']) ? (int)$other['qtd_normal_money_fy'] : 0;

        for ($i = 0; $i < 5; $i++) {
            $record = $records[$i];
            $data[] = $record;
            $other['director_fore_money'] -= (int)$record['director_fore_money'];
            $other['q_forecast'] -= (int)$record['q_forecast'];
            $other['q_money_fq'] -= (int)$record['q_money_fq'];
            $other['q_money_fy'] -= (int)$record['q_money_fy'];
            $other['q_opp'] -= (int)$record['q_opp'];
            $other['q_opp_ongoing'] -= (int)$record['q_opp_ongoing'];
            $other['q_opp_order'] -= (int)$record['q_opp_order'];
            $other['q_opp_remain'] -= (int)$record['q_opp_remain'];
            $other['q_task'] -= (int)$record['q_task'];
            $other['q_wip'] -= (int)$record['q_wip'];
            $other['qtd_business_money'] -= (int)$record['qtd_business_money'];
            $other['qtd_business_money_fy'] -= (int)$record['qtd_business_money_fy'];
            $other['qtd_money'] -= (int)$record['qtd_money'];
            $other['qtd_money_fq'] -= (int)$record['qtd_money_fq'];
            $other['qtd_money_fy'] -= (int)$record['qtd_money_fy'];
            $other['qtd_normal_money'] -= (int)$record['qtd_normal_money'];
            $other['qtd_normal_money_fy'] -= (int)$record['qtd_normal_money_fy'];
        }
        $directorForeMoneyLost = NumberUtil::formatNumber(($other['q_task'] ?? 0) - $other['director_fore_money']);
        $directorForeMoneyFinishRate = NumberUtil::formatRate($other['q_task'] ?? 0,
            $other['director_fore_money'] ?? 0);
        $forecastFinishRate = NumberUtil::formatRate($other['q_task'] ?? 0, $other['q_forecast'] ?? 0);
        $forecastGap = NumberUtil::formatNumber($other['q_forecast'] ?? 0 - $other['q_task'] ?? 0);
        $qMoneyYoy = NumberUtil::formatRate($other['qtd_money_fy'] ?? 0, $other['qtd_money'] ?? 0, 1);
        $qMoneyQoq = NumberUtil::formatRate($other['qtd_money_fq'] ?? 0, $other['qtd_money'] ?? 0, 1);
        $qOppFinishRate = NumberUtil::formatRate($other['q_task'] ?? 0, $other['q_opp'] ?? 0);
        $qtdFinishRate = NumberUtil::formatRate($other['q_task'] ?? 0, $other['qtd_money'] ?? 0);
        $qtdFinishRateFy = NumberUtil::formatRate($other['q_money_fy'] ?? 0, $other['qtd_money_fy'] ?? 0);
        $yoy = intval($qtdFinishRate) - intval($qtdFinishRateFy);
        $other['director_fore_money_lost'] = $directorForeMoneyLost;
        $other['director_fore_money_finish_rate'] = $directorForeMoneyFinishRate;
        $other['forecast_finish_rate'] = $forecastFinishRate;
        $other['forecast_gap'] = $forecastGap;
        $other['q_money_yoy'] = $qMoneyYoy;
        $other['q_money_qoq'] = $qMoneyQoq;
        $other['q_opp_finish_rate'] = $qOppFinishRate;
        $other['qtd_finish_rate'] = $qtdFinishRate;
        $other['qtd_finish_rate_fy'] = $qtdFinishRateFy;
        $other['yoy'] = $yoy;
        $other["dig"] = -1;//下钻禁止
        $other["arch_id"] = "";
        $other["arch_name"] = "其他";
        $other["arch_type"] = RevenueConst::ARCH_TYPE_SHORT;
        $data[] = $other;
        return $data;
    }

    protected function getTopData($tree, $limit, $nameField = 'arch_name')
    {
        $result = [];
        $total = 0;
        $others = 0;
        usort($tree, function ($a, $b) {
            $aQtdMoney = (int)$a['qtd_money'];
            $bQtdMoney = (int)$b['qtd_money'];
            return $aQtdMoney < $bQtdMoney;
        });
        $i = 0;
        foreach ($tree as $rootNode) {
            $i++;
            $total += $rootNode['qtd_money'];
            if ($i > $limit) {
                $others += $rootNode['qtd_money'];
            }
        }
        $i = 0;
        $isOthersAlreadyIn = false;
        foreach ($tree as $rootNode) {
            $i++;
            if ($i > $limit) {
                continue;
            }
            $node = &$result[];
            $node['name'] = $rootNode[$nameField];
            $node['money'] = intval($rootNode['qtd_money']);
            $node['ratio'] = ($total > 0) ? round($rootNode['qtd_money'] / $total * 100) : 100;
            if ($rootNode[$nameField] == '其他') {
                $isOthersAlreadyIn = true;
            }
        }
        if (!$isOthersAlreadyIn && count($tree) > $limit) {
            $result[] = [
                'name' => '其他',
                'money' => $others,
                'ratio' => ($total > 0) ? round($others / $total * 100) : 100
            ];
        }
        return $result;
    }

    protected function isLeaderLevel($archType)
    {
        $aboveSaleArchTypeArr = [
            RevenueConst::ARCH_TYPE_TEAM,
            RevenueConst::ARCH_TYPE_DIRECTOR,
            RevenueConst::ARCH_TYPE_AREA,
            RevenueConst::ARCH_TYPE_DEPT,
            RevenueConst::ARCH_TYPE_NATION,
        ];
        return in_array($archType, $aboveSaleArchTypeArr);
    }

    protected function makeSort($year, $quarter, $archType, $records, $sortBy = 'qtd_money', $sortType = 'desc')
    {
        if (empty($sortBy)) {
            switch ($archType) {
                case RevenueConst::ARCH_TYPE_DEPT:
                    /**
                     * @var $rs RevenueService
                     */
                    $rs = app()->make(RevenueService::class);
                    $records = $rs->orderRevenueDataByArea($year, $quarter, $records);
                    break;
                default:
            }
        } else {
            $sort = $sortType == "desc" ? SORT_DESC : SORT_ASC;
            Utils::arraySort($records, $sortBy, $sort);
        }
        return $records;
    }

    protected function addProductsTreeArchName(&$tree)
    {
        foreach ($tree as &$child) {
            if (!array_key_exists('arch_name', $child)) {
                $child['arch_name'] = $child['product'];
            }
            if (array_key_exists('children', $child)) {
                $this->addProductsTreeArchName($child['children']);
            }
        }
    }

    public function timeRange(Request $request)
    {
        $channelType = $request->input('channel_type', 'direct');
        /**
         * @var $rs RevenueService
         */
        $rs = app()->make(RevenueService::class);
        $timeStrArr = $rs->getRevenueTimeRange($channelType);
        $total = count($timeStrArr);
        $data = [];
        if (!empty($timeStrArr)) {
            $result = [];
            $today = Carbon::today();
            foreach ($timeStrArr as $v) {
                $time = Carbon::parse($v);
                $q = $time->year . "Q" . $time->quarter;
                if ($today->year == $time->year && $today->quarter == $time->quarter) {
                    $defaultQ = $q;
                }
                $label = $time->year . '年Q' . $time->quarter;
                $result[] = [
                    'value' => $q,
                    'label' => $label
                ];
            }

            $data = [
                'total' => $total,
                'records' => $result,
                'default' => !empty($defaultQ) ? $defaultQ : $result[0]['value']
            ];

        }
        return $this->success($data);
    }

    protected function processForNationRevenue(
        $records,
        $year,
        $quarter,
        $saleId,
        $product,
        $incomeType,
        $channelType,
        $sortBy,
        $sortType
    ) {
        //查看全国的要特殊处理一下，让helenluan看的时候，records里面有children字段，对应查询部门的下面中心的数据
        /**
         * @var RevenueService $revenueService
         */
        $revenueService = app()->make(RevenueService::class);
        $archType = RevenueConst::ARCH_TYPE_DEPT;
        $ret = [];
        foreach ($records as $record) {
            $drillId = $record['arch_id'];
            $areaRecords = $revenueService->getDrillArchRevenues($year, $quarter, $drillId,
                $archType, $saleId, $product, $incomeType, $channelType);

            if ($archType == RevenueConst::ARCH_TYPE_DEPT && $channelType == 'channel') {
                //渠道获取下级片区业绩的时候，过滤 渠道拓新业务中心
                //72134738-5983-11E8-AFD7-F02FA73BC80D
                $tmp = [];
                foreach ($areaRecords as $row) {
                    if ($row['arch_id'] != '72134738-5983-11E8-AFD7-F02FA73BC80D' && !\in_array($row['arch_id'],
                            ArchitectConstant::$smbDirectAreas)) {
                        $tmp[] = $row;
                    }
                }
                $areaRecords = $tmp;
            }

            if ($archType == RevenueConst::ARCH_TYPE_DEPT && $channelType == 'direct') {
                $tmp = [];
                foreach ($areaRecords as $row) {
                    if (!\in_array($row['arch_id'],
                        ArchitectConstant::$smbChannelAreas)) {
                        $tmp[] = $row;
                    }
                }
                $areaRecords = $tmp;
            }

            $areaRecords = $this->makeSort($year, $quarter, $archType, $areaRecords, $sortBy, $sortType);
            $ret[$drillId] = $areaRecords;
        }
        return $ret;
    }

    public function architects(Request $request)
    {
        //TODO: 兼容一下，如果不传team_id和drill_id的时候，默认把登录用户的最高的team都拉出来
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $period = $request->input('period');
        $channelType = $request->input('channel_type');
        $archType = $request->input('arch_type');
        $teamId = $request->input('team_id');
        //$saleId = $request->input('sale_id');
        $drillId = $request->input('drill_id');
        $drillType = $request->input('arch_type_drill');

        if (!empty($period)) {
            list($year, $quarter) = explode('Q', $period);
        }
        if (empty($year) && empty($quarter) && empty($period)) {
            $today = new Carbon();
            $year = $today->year;
            $quarter = $today->quarter;
        }
        if (!empty($drillType)) {
            $archType = $drillType;
        }
        if (empty($drillId)) {
            $drillId = $teamId;
        }
        /**
         * @var User $user
         */
        $user = Auth::user();
        /**
         * @var ArchitectService $architectService
         */
        $architectService = app()->make(ArchitectService::class);
        if (empty($drillId)) {
            //如果drill_id为空，获取当前用户最高层级的team
            $data = $architectService->getUserHighestArch($user, $year, $quarter);
        } else {
            $data = $architectService->getArchData($drillId, $year, $quarter, $archType, $channelType);
        }

        $records = [];
        foreach ($data as $row) {
            if (array_key_exists('sale_id', $row)) {
                $row['arch_id'] = $row['sale_id'];
                $row['arch_type'] = RevenueConst::ARCH_TYPE_SALE;
                $row['arch_pid'] = $drillId;
                $row['arch_name'] = $row['name'];
                $records[] = $row;
                continue;
            }
            if (array_key_exists('team_id', $row)) {
                unset($row['owner']);
                $level = $row['level'];
                $archType = RevenueConst::$teamLevelToArchType[$level];
                $row['arch_id'] = $row['team_id'];
                $row['arch_type'] = $archType;
                $row['arch_pid'] = $drillId ?? $row['pid'];
                $row['arch_name'] = $row['name'];
                $records[] = $row;
                continue;
            }
        }
        return $this->success(['records' => $records]);
    }
}

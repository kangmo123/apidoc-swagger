<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/7/10
 * Time: 2:05 PM.
 */

namespace App\Http\Controllers;

use App\Repositories\Area\AreaFilter;
use App\Repositories\Area\AreaRepository;
use App\Repositories\Centre\CentreFilter;
use App\Repositories\Centre\CentreRepository;
use App\Repositories\Client\ClientFilter;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Department\DepartmentFilter;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Nation\NationFilter;
use App\Repositories\Nation\NationRepository;
use App\Repositories\Project\ProjectFilter;
use App\Repositories\Project\ProjectRepository;
use App\Repositories\QuarterFilter;
use App\Repositories\Sale\SaleFilter;
use App\Repositories\Sale\SaleRepository;
use App\Repositories\Team\TeamFilter;
use App\Repositories\Team\TeamRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Opportunity;
use App\Models\Forecast;
use App\Http\Resources\ForecastResource;
use App\Services\ConstDef\OpportunityDef;
use App\Services\ForecastSearchService;
use Illuminate\Pagination\LengthAwarePaginator;

class ForecastController extends Controller
{
    /**
     * @api      {GET} /opportunities/{opportunity_id}/forecasts 获取商机关联的预估列表
     * @apiGroup Opportunity
     * @apiName  ListForecast
     *
     * @apiUse   ForecastCollectionResource
     *
     * @param Request $request
     *
     * @return \App\Library\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, $opportunityId)
    {
        $opportunity   = Opportunity::findByOpportunityIdOrFail($opportunityId);
        $items         = Forecast::getForecasts($opportunity);
        return ForecastResource::collection($items);
    }

    /**
     * @api      {GET} /opportunity-forecasts/{forecast_id} 查看商机预估
     * @apiGroup Forecast
     * @apiName  ShowForecast
     *
     * @apiUse   ForecastItemResource
     * @apiUse   NotFound
     *
     * @param $opportunityId
     *
     * @return AgentResource
     */
    public function show($forecastId)
    {
        return ForecastResource::item(Forecast::findByForecastIdOrFail($forecastId));
    }

    /**
     * @api {GET} /opportunity-forecasts 查询商机预估
     * @apiGroup Forecast
     * @apiName SearchForecasts
     * @apiDescription 如果查询参数值为0，需要使用字符串格式，否则会被忽略，例如 order_money = '0'
     * @apiParam {String} opportunity_id 商机 GUID，非必填，多个 id 用英文逗号,隔开
     * @apiParam {String} forecast_id 商机预估 GUID，非必填，多个 id 用英文逗号,隔开
     * @apiParam {Number} year 年份，非必填
     * @apiParam {Number} q 季度，非必填
     * @apiParam {Number} forecast_money 预估金额，非必填
     * @apiParam {Number} forecast_money_remain 预估剩余金额，非必填
     * @apiParam {Number} order_money 下单金额，非必填
     * @apiParam {Number} order_rate 完成率，非必填
     * @apiParam {Number} video_forecast_money 视频预估金额，非必填
     * @apiParam {Number} video_order_money 视频下单金额，非必填
     * @apiParam {Number} video_order_rate 视频下单完成率，非必填
     * @apiParam {Number} video_forecast_money_remain 视频预估剩余金额，非必填
     * @apiParam {Number} news_forecast_money 新闻预估金额，非必填
     * @apiParam {Number} news_order_rate 新闻下单完成率，非必填
     * @apiParam {Number} news_order_money 新闻下单金额，非必填
     * @apiParam {Number} news_forecast_money_remain 新闻剩余预估金额，非必填
     * @apiParam {Date} begin 预估投放的开始日期，非必填
     * @apiParam {Date} end 预估投放的结束日期，非必填
     * @apiParam {String} created_by 创建人 RTX，多个字段用逗号隔开，非必填
     * @apiParam {Datetime} created_at 创建时间，非必填
     * @apiParam {String} updated_by 更新人 RTX，多个字段用逗号隔开，非必填
     * @apiParam {Datetime} updated_at 更新时间，非必填
     * @apiParam {Array} operator 自定义查询字段的操作符，例如 operator[order_money]='>'
     * @apiParam {Srting} toSql 调试模式，只输出查询 SQL 语句
     * @apiParam {String} sort 排序方式，+升序，-降序，如'+id,-updated_at'
     * @apiParam {Number} page=1 数据页数，1,2,3……
     * @apiParam {Number} per_page=20 每页数据量
     *
     * @apiUse OpportunityCollectionResource
     *
     * @param Request $request
     *
     * @return array
     */
    public function search(Request $request)
    {
        try {
            $page    = $request->input('page', OpportunityDef::DEFAULT_PAGE);
            $perPage = $request->input('per_page', OpportunityDef::DEFAULT_PER_PAGE);

            $searchService    = new ForecastSearchService($request);
            $total            = $searchService->count();

            $opportunities    = $total ? Forecast::findByForecastIds($searchService->getIds()) : [];

            $paginator        = new LengthAwarePaginator($opportunities, $total, $perPage, $page);
            return ForecastResource::collection($paginator);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }

    /**
     * @api            {get} /forecast/sales  获取'销售-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'销售ID,小组ID, 季度, 产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         SaleFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&sale_id=1,2&team_id=1&page=1&per_page=20&sort=+opp_q_opp
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "sale_id": "1",
     *          "team_id": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "product": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request        $request
     * @param SaleRepository $saleRepository
     * @return Response
     */
    public function sale(Request $request, SaleRepository $saleRepository): Response
    {
        $data = $saleRepository->getQuarterly(new SaleFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/teams 获取'小组-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'小组ID, 季度, 产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         TeamFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&team_id=1,2&centre_id=1&page=1&per_page=20&sort=+opp_q_opp
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "team_id": "1",
     *          "centre_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request        $request
     * @param TeamRepository $repository
     * @return Response
     */
    public function team(Request $request, TeamRepository $repository): Response
    {
        $data = $repository->getQuarterly(new TeamFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/centres 获取'中心-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'中心ID, 季度, 产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         CentreFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&centre_id=1,2&page=1&per_page=20&sort=+opp_q_wip
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "area_id": "1",
     *          "centre_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request          $request
     * @param CentreRepository $repository
     * @return Response
     */
    public function centre(Request $request, CentreRepository $repository): Response
    {
        $data = $repository->getQuarterly(new CentreFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/areas 获取'片区-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'片区ID, 季度, 产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         AreaFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&area_id=1,2&page=1&per_page=20&sort=+opp_q_wip
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "area_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request        $request
     * @param AreaRepository $repository
     * @return Response
     */
    public function area(Request $request, AreaRepository $repository): Response
    {
        $data = $repository->getQuarterly(new AreaFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/departments 获取'部门-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'部门ID, 季度, 产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         DepartmentFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&department_id=1,2&page=1&per_page=20&sort=+opp_q_wip
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "department_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request              $request
     * @param DepartmentRepository $repository
     * @return Response
     */
    public function department(Request $request, DepartmentRepository $repository): Response
    {
        $data = $repository->getQuarterly(new DepartmentFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/nation 获取'全国-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'季度, 产品类型'聚合的预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         NationFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2,3&page=1&per_page=20&sort=+opp_q_opp
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request          $request
     * @param NationRepository $repository
     * @return Response
     */
    public function nation(Request $request, NationRepository $repository): Response
    {
        $data = $repository->getQuarterly(new NationFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/clients 获取'客户-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'客户ID,小组ID,销售ID,季度,产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         ClientFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&client_id=1,2&team_id=1&sale_id=2&page=1&per_page=20&sort=+opp_q_wip
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "client_id": "1",
     *          "agent_id": "1",
     *          "sale_id": "1",
     *          "team_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request          $request
     * @param ClientRepository $repository
     * @return Response
     */
    public function client(Request $request, ClientRepository $repository): Response
    {
        $data = $repository->getQuarterly(new ClientFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }

    /**
     * @api            {get} /forecast/projects 获取'招商项目-季度'级别商机预估数据
     * @apiDescription 根据查询条件,获取按'招商项目ID,小组ID,销售ID,季度,产品类型'聚合的商机预估数据
     * @apiGroup       Forecast
     * @apiUse         QuarterFilter
     * @apiUse         ProjectFilter
     * @apiParamExample {string} 请求参数格式:
     *    ?year=2018&quarter=2&project_id=1,2&team_id=1&sale_id=2&page=1&per_page=20&sort=+opp_q_wip
     * @apiVersion     1.0.0
     * @apiSuccessExample {json} 正确返回值:
     *    {
     *      "code": 0,
     *      "msg": "OK",
     *      "data": [
     *        {
     *          "project_id": "1",
     *          "client_id": "1",
     *          "sale_id": "1",
     *          "team_id": "1",
     *          "product": 1,
     *          "year": 2018,
     *          "quarter": 1,
     *          "opp_q_forecast": 100,
     *          "opp_q_wip": 100,
     *          "opp_q_ongoing": 100,
     *          "opp_q_order": 100,
     *          "opp_q_remain": 100,
     *        }
     *      ]
     *    }
     */

    /**
     * @param Request $request
     * @param ProjectRepository $repository
     * @return Response
     */
    public function project(Request $request, ProjectRepository $repository): Response
    {
        $data = $repository->getQuarterLy(new ProjectFilter($request), new QuarterFilter($request));
        return new Response([
            'code'        => 0,
            'msg'         => 'OK',
            'data'        => $data,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Http\Resources\OpportunityResource;
use Illuminate\Http\Request;
use App\Services\OpportunityCreateService;
use App\Http\Request\CreateOpportunityRequest;
use App\Http\Request\UpdateOpportunityRequest;
use App\Services\OpportunityUpdateService;
use App\Http\Request\CheckOppNameExistRequest;
use App\Exceptions\Business\BusinessException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OpportunitySearchService;
use App\Services\ConstDef\OpportunityDef;

class OpportunityController extends Controller
{
    /**
     * @api      {POST} /opportunities 创建商机
     * @apiGroup Opportunity
     * @apiName  CreateOpportunity

     * @apiParam {String} opp_name 商机名称，必填
     * @apiParam {Integer} data_from 商机数据来源：0-未知,1-crm_s,2-crm_ngs,3-win,4-twin，非必填
     * @apiParam {String} client_id 客户ID，非必填
     * @apiParam {String} short_id 客户简称id，非必填
     * @apiParam {String} agent_id 代理商id，非必填
     * @apiParam {String} brand_id 品牌id，非必填
     * @apiParam {Integer} belong_to 商机归属，1-销售，2-渠道，非必填
     * @apiParam {Integer} is_share 是否共享，0-否，1-是，非必填
     * @apiParam {String} owner_rtx 负责人rtx，非必填
     * @apiParam {String} sale_rtx 销售rtx，非必填
     * @apiParam {String} channel_rtx 渠道rtx，非必填
     * @apiParam {Date} order_date 预计签单时间，非必填
     * @apiParam {String} onboard_begin 投放开始时间，非必填
     * @apiParam {String} onboard_end 投放结束时间，非必填
     * @apiParam {Number} forecast_money 预估金额，非必填
     * @apiParam {Number} forecast_money_remain 预估剩余金额，非必填
     * @apiParam {String} step 商机阶段，0-未知，1-初步意向，2-跟进中，3-即将锁单，4-锁单即将下单，5-WIP，6-赢单，7-失单，非必填
     * @apiParam {Number} probability 赢单概率，非必填
     * @apiParam {Number} manager_probability 主管确认赢单概率，非必填
     * @apiParam {String} step_comment 商机阶段说明，非必填
     * @apiParam {Integer} risk_type 商机风险类型 ，0-未知，1-暂无风险，2-库存问题，3-资源问题，4-预算问题，5-价格问题，6-其他，非必填
     * @apiParam {String} risk_comment 商机风险说明，非必填
     * @apiParam {Integer} opp_type 商机类型，1-普通商机，2-智赢销商机，3-汇赢商机，非必填
     * @apiParam {Integer} status 商机状态，默认1，1-正在进行;2-暂候;3-赢单;4-失单;5-取消;200000-重复;200001-合并，非必填
     * @apiParam {Integer} is_crucial 是否攻坚团队，0-否，1-是，非必填
     * @apiParam {String} crucial_rtx 攻坚团队人员rtx，非必填
     * @apiParam {Integer} opp_resource 商机来源，0-未知，1-渠道，2-直客，3-公司内部，4-其他，5-攻坚团队，非必填
     * @apiParam {Integer} frame_type 框架类型，0-未知，1-直客框架，2-代理框架，3-无框架，4-未定，非必填
     * @apiParam {Integer} help_type 所需支持类型，0-未知，1-代理支持(关系维护、政策支持、战略合作)，2-客户支持(关系维护、销售政策)，3-市场支持(会议营销、行业地位)，4-策划支持(策略倾向)，5-产品支持(特殊产品、频道内容支持)，非必填
     * @apiParam {String} help_comment 所需支持说明，非必填
     * @apiParam {Date} close_date 商机关闭时间，非必填
     * @apiParam {Number} close_value 商机关闭实际收入，非必填
     * @apiParam {String} close_comment 商机关闭备注说明，非必填
     * @apiParam {Number} order_money 下单金额，非必填
     * @apiParam {Number} order_rate 完成率，非必填
     * @apiParam {Number} order_rate_real_time 实时完成率，非必填
     * @apiParam {Number} video_forecast_money 视频预估金额，非必填
     * @apiParam {Number} video_order_money 视频下单金额，非必填
     * @apiParam {Number} video_order_rate 视频下单完成率，非必填
     * @apiParam {Number} video_forecast_money_remain 视频预估剩余金额，非必填
     * @apiParam {Number} news_forecast_money 新闻预估金额，非必填
     * @apiParam {Number} news_order_rate 新闻下单完成率，非必填
     * @apiParam {Number} news_order_money 新闻下单金额，非必填
     * @apiParam {Number} news_forecast_money_remain 新闻剩余预估金额，非必填
     * @apiParam {Array} forecasts 商机预估信息数组，非必填
     * @apiParam {Array} forecasts.details 商机预估详情信息数组，非必填

     * @apiUse   OpportunityItemResource
     * @apiUse   ValidationFailed
     *
     * @throws BusinessException
     * @throws \Throwable
     */
    public function create(CreateOpportunityRequest $request)
    {
        try {
            $this->beginTransaction();
            $opportunityService = new OpportunityCreateService();
            $opportunityId = $opportunityService->create($request);
            $this->commit();

            $opportunity = Opportunity::findByOpportunityIdOrFail($opportunityId);
            return OpportunityResource::item($opportunity);
        } catch (\Exception $e) {
            $this->rollBack();
            return $this->dealException($e);
        }
    }

    /**
     * @api      {PUT} /opportunities/{opportunity_id} 修改商机
     * @apiGroup Opportunity
     * @apiName  UpdateOpportunity
     *
     * @apiParam {Integer} data_from 商机数据来源：0-未知,1-crm_s,2-crm_ngs,3-win,4-twin，非必填
     * @apiParam {String} opp_name 商机名称，非必填
     * @apiParam {String} client_id 客户ID，非必填
     * @apiParam {String} short_id 客户简称id，非必填
     * @apiParam {String} agent_id 代理商id，非必填
     * @apiParam {String} brand_id 品牌id，非必填
     * @apiParam {Integer} belong_to 商机归属，1-销售，2-渠道，非必填
     * @apiParam {Integer} is_share 是否共享，0-否，1-是，非必填
     * @apiParam {String} owner_rtx 负责人rtx，非必填
     * @apiParam {String} sale_rtx 销售rtx，非必填
     * @apiParam {String} channel_rtx 渠道rtx，非必填
     * @apiParam {Date} order_date 预计签单时间，非必填
     * @apiParam {String} onboard_begin 投放开始时间，非必填
     * @apiParam {String} onboard_end 投放结束时间，非必填
     * @apiParam {Number} forecast_money 预估金额，非必填
     * @apiParam {Number} forecast_money_remain 预估剩余金额，非必填
     * @apiParam {String} step 商机阶段，0-未知，1-初步意向，2-跟进中，3-即将锁单，4-锁单即将下单，5-WIP，6-赢单，7-失单，非必填
     * @apiParam {Number} probability 赢单概率，非必填
     * @apiParam {Number} manager_probability 主管确认赢单概率，非必填
     * @apiParam {String} step_comment 商机阶段说明，非必填
     * @apiParam {Integer} risk_type 商机风险类型 ，0-未知，1-暂无风险，2-库存问题，3-资源问题，4-预算问题，5-价格问题，6-其他，非必填
     * @apiParam {String} risk_comment 商机风险说明，非必填
     * @apiParam {Integer} opp_type 商机类型，1-普通商机，2-智赢销商机，3-汇赢商机，非必填
     * @apiParam {Integer} status 商机状态，默认1，1-正在进行;2-暂候;3-赢单;4-失单;5-取消;200000-重复;200001-合并，非必填
     * @apiParam {Integer} is_crucial 是否攻坚团队，0-否，1-是，非必填
     * @apiParam {String} crucial_rtx 攻坚团队人员rtx，非必填
     * @apiParam {Integer} opp_resource 商机来源，0-未知，1-渠道，2-直客，3-公司内部，4-其他，5-攻坚团队，非必填
     * @apiParam {Integer} frame_type 框架类型，0-未知，1-直客框架，2-代理框架，3-无框架，4-未定，非必填
     * @apiParam {Integer} help_type 所需支持类型，0-未知，1-代理支持(关系维护、政策支持、战略合作)，2-客户支持(关系维护、销售政策)，3-市场支持(会议营销、行业地位)，4-策划支持(策略倾向)，5-产品支持(特殊产品、频道内容支持)，非必填
     * @apiParam {String} help_comment 所需支持说明，非必填
     * @apiParam {Date} close_date 商机关闭时间，非必填
     * @apiParam {Number} close_value 商机关闭实际收入，非必填
     * @apiParam {String} close_comment 商机关闭备注说明，非必填
     * @apiParam {Number} order_money 下单金额，非必填
     * @apiParam {Number} order_rate 完成率，非必填
     * @apiParam {Number} order_rate_real_time 实时完成率，非必填
     * @apiParam {Number} video_forecast_money 视频预估金额，非必填
     * @apiParam {Number} video_order_money 视频下单金额，非必填
     * @apiParam {Number} video_order_rate 视频下单完成率，非必填
     * @apiParam {Number} video_forecast_money_remain 视频预估剩余金额，非必填
     * @apiParam {Number} news_forecast_money 新闻预估金额，非必填
     * @apiParam {Number} news_order_rate 新闻下单完成率，非必填
     * @apiParam {Number} news_order_money 新闻下单金额，非必填
     * @apiParam {Number} news_forecast_money_remain 新闻剩余预估金额，非必填
     * @apiParam {Array} forecasts 商机预估信息数组，非必填
     * @apiParam {Array} forecasts.details 商机预估详情信息数组，非必填
     *
     * @apiUse   opportunityUpdateResource
     * @apiUse   OpportunityItemResource
     * @apiUse   ValidationFailed
     *
     * @param UpdateOpportunityRequest $request
     * @param string                   $opportunityId
     *
     * @return OpportunityResource
     *
     * @throws \Throwable
     */
    public function update(UpdateOpportunityRequest $request, $opportunityId)
    {
        try {
            $this->beginTransaction();
            (new OpportunityUpdateService())->update($request, $opportunityId);
            $this->commit();

            $opportunity = Opportunity::findByOpportunityIdOrFail($opportunityId);
            return OpportunityResource::item($opportunity);
        } catch (\Exception $e) {
            $this->rollBack();
            return $this->dealException($e);
        }
    }

    /**
     * @api      {GET} /opportunities/{opportunity_id} 查看商机详情
     * @apiGroup Opportunity
     * @apiName  ShowOpportunity
     *
     * @apiUse   OpportunityItemResource
     * @apiUse   NotFound
     *
     * @param Request $request
     * @param         $opportunityId
     *
     * @return OpportunityResource
     */
    public function show(Request $request, $opportunityId)
    {
        try {
            $opportunity = Opportunity::findByOpportunityIdOrFail($opportunityId);
            return OpportunityResource::item($opportunity);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }

    /**
     * @api      {GET} /opportunities/name 查看商机名称是否重复
     * @apiGroup Opportunity
     * @apiName  CheckOpportunityName
     *
     * @apiParam {String} name 商机名称，必填
     * @apiParam {String} opportunity_id 商机 ID，非必填
     *
     * @apiSuccessExample
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "exist": true
     *     }
     * }
     *
     * @param CheckOppNameExistRequest $request
     * @param                          $opportunityId
     *
     * @return bollean
     *
     * @throws BusinessException
     */
    public function name(CheckOppNameExistRequest $request)
    {
        try {
            $data = [
                'exist' => Opportunity::nameExist($request->getOppName(), $request->getOpportunityId()),
            ];
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }

    /**
     * @api {GET} /opportunities/search 查询商机列表
     * @apiGroup Opportunity
     * @apiName SearchOpportunity
     * @apiDescription 如果查询参数值为0，需要使用字符串格式，否则会被忽略，例如 order_money = '0'
     *
     * @apiParam {String} opportunity_id 商机 GUID，非必填，多个 id 用英文逗号,隔开
     * @apiParam {String} opp_code 商机编码，非必填，多个 code 用英文逗号,隔开
     * @apiParam {Integer} data_from 商机数据来源：0-未知,1-crm_s,2-crm_ngs,3-win,4-twin，非必填
     * @apiParam {String} opp_name 商机名称，非必填，支持模糊搜索
     * @apiParam {String} client_id 客户ID，非必填
     * @apiParam {String} short_id 客户简称id，非必填
     * @apiParam {String} agent_id 代理商id，非必填
     * @apiParam {String} brand_id 品牌id，非必填
     * @apiParam {Integer} belong_to 商机归属，1-销售，2-渠道，非必填
     * @apiParam {Integer} is_share 是否共享，0-否，1-是，非必填
     * @apiParam {String} owner_rtx 负责人rtx，非必填
     * @apiParam {String} sale_rtx 销售rtx，非必填
     * @apiParam {String} channel_rtx 渠道rtx，非必填
     * @apiParam {Date} order_date 预计签单时间，非必填
     * @apiParam {String} onboard_begin 投放开始时间，非必填
     * @apiParam {String} onboard_end 投放结束时间，非必填
     * @apiParam {Number} forecast_money 预估金额，非必填
     * @apiParam {Number} forecast_money_remain 预估剩余金额，非必填
     * @apiParam {String} step 商机阶段，0-未知，1-初步意向，2-跟进中，3-即将锁单，4-锁单即将下单，5-WIP，6-赢单，7-失单，非必填
     * @apiParam {Number} probability 赢单概率，非必填
     * @apiParam {Number} manager_probability 主管确认赢单概率，非必填
     * @apiParam {String} step_comment 商机阶段说明，非必填
     * @apiParam {Integer} risk_type 商机风险类型 ，0-未知，1-暂无风险，2-库存问题，3-资源问题，4-预算问题，5-价格问题，6-其他，非必填
     * @apiParam {String} risk_comment 商机风险说明，非必填
     * @apiParam {Integer} opp_type 商机类型，1-普通商机，2-智赢销商机，3-汇赢商机，非必填
     * @apiParam {Integer} status 商机状态，默认1，1-正在进行;2-暂候;3-赢单;4-失单;5-取消;200000-重复;200001-合并，非必填
     * @apiParam {Integer} is_crucial 是否攻坚团队，0-否，1-是，非必填
     * @apiParam {String} crucial_rtx 攻坚团队人员rtx，非必填
     * @apiParam {Integer} opp_resource 商机来源，0-未知，1-渠道，2-直客，3-公司内部，4-其他，5-攻坚团队，非必填
     * @apiParam {Integer} frame_type 框架类型，0-未知，1-直客框架，2-代理框架，3-无框架，4-未定，非必填
     * @apiParam {Integer} help_type 所需支持类型，0-未知，1-代理支持(关系维护、政策支持、战略合作)，2-客户支持(关系维护、销售政策)，3-市场支持(会议营销、行业地位)，4-策划支持(策略倾向)，5-产品支持(特殊产品、频道内容支持)，非必填
     * @apiParam {String} help_comment 所需支持说明，非必填
     * @apiParam {Date} close_date 商机关闭时间，非必填
     * @apiParam {Number} close_value 商机关闭实际收入，非必填
     * @apiParam {String} close_comment 商机关闭备注说明，非必填
     * @apiParam {Number} order_money 下单金额，非必填
     * @apiParam {Number} order_rate 完成率，非必填
     * @apiParam {Number} order_rate_real_time 实时完成率，非必填
     * @apiParam {Number} video_forecast_money 视频预估金额，非必填
     * @apiParam {Number} video_order_money 视频下单金额，非必填
     * @apiParam {Number} video_order_rate 视频下单完成率，非必填
     * @apiParam {Number} video_forecast_money_remain 视频预估剩余金额，非必填
     * @apiParam {Number} news_forecast_money 新闻预估金额，非必填
     * @apiParam {Number} news_order_rate 新闻下单完成率，非必填
     * @apiParam {Number} news_order_money 新闻下单金额，非必填
     * @apiParam {Number} news_forecast_money_remain 新闻剩余预估金额，非必填
     * @apiParam {String} created_by 创建人 RTX，多个字段用逗号隔开，非必填
     * @apiParam {Datetime} created_at 创建时间，非必填
     * @apiParam {String} updated_by 更新人 RTX，多个字段用逗号隔开，非必填
     * @apiParam {Datetime} updated_at 更新时间，非必填
     * @apiParam {Datetime} updated_at_start 更新时间起，非必填
     * @apiParam {Datetime} updated_at_end 更新时间止，非必填
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
            $page = $request->input('page', OpportunityDef::DEFAULT_PAGE);
            $perPage = $request->input('per_page', OpportunityDef::DEFAULT_PER_PAGE);

            $perPage = min($perPage, OpportunityDef::MAX_PER_PAGE);
            $searchService = new OpportunitySearchService($request);
            $total = $searchService->count();

            $opportunities = $total ? Opportunity::findByOpportunityIds($searchService->getIds()) : [];

            $paginator = new LengthAwarePaginator($opportunities, $total, $perPage, $page);
            return OpportunityResource::collection($paginator);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }
}

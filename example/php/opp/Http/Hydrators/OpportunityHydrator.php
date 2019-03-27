<?php

namespace App\Http\Hydrators;

use App\Models\Model;
use App\Models\Opportunity;
use App\Services\ConstDef\OpportunityDef;

/**
 * @apiDefine       CreateOpportunityParams
 * @apiParam {String} opp_name 商机名称，必填
 * @apiParam {Number} data_from 商机数据来源：0-未知,1-crm_s,2-crm_ngs,3-win,4-twin
 * @apiParam {String} client_id 直接客户ID
 * @apiParam {String} short_id 客户简称ID
 * @apiParam {String} agent_id 策略代理ID
 * @apiParam {String} brand_id 品牌产品ID
 * @apiParam {Number} belong_to 商机归属，1-销售，2-渠道
 * @apiParam {Number} is_share 是否共享，0-否，1-是
 * @apiParam {String} owner_rtx 商机负责人
 * @apiParam {String} sale_rtx 销售rtx
 * @apiParam {String} channel_rtx 渠道rtx
 * @apiParam {Date} order_date 预计签单时间
 * @apiParam {Date} onboard_begin 投放开始时间
 * @apiParam {Date} onboard_end 投放结束时间
 * @apiParam {Number} forecast_money 预估投放总金额
 * @apiParam {Number} forecast_money_remain 剩余预估金额
 * @apiParam {Number} step 商机阶段，0-未知，1-初步意向，2-跟进中，3-即将锁单，4-锁单即将下单，5-WIP，6-赢单，7-失单
 * @apiParam {Number} probability 赢单概率
 * @apiParam {Number} manager_probability 主管确认赢单概率
 * @apiParam {String} step_comment 商机阶段说明
 * @apiParam {Number} risk_type 商机风险类型，0-未知，1-暂无风险，2-库存问题，3-资源问题，4-预算问题，5-价格问题，6-其他
 * @apiParam {String} risk_comment 商机风险说明
 * @apiParam {Number} opp_type 商机类型
 * @apiParam {Number} status 商机状态，默认1，1-正在进行;2-暂候;3-赢单;4-失单;5-取消;200000-重复;200001-合并
 * @apiParam {Number} is_crucial 是否攻坚团队，0-否，1-是
 * @apiParam {String} crucial_rtx 攻坚团队人员，Fis_cucial=1时必填
 * @apiParam {Number} opp_resource 商机来源，0-未知，1-渠道，2-直客，3-公司内部，4-其他，5-攻坚团队
 * @apiParam {Number} frame_type 框架类型，0-未知，1-直客框架，2-代理框架，3-无框架，4-未定
 * @apiParam {Number} help_type 所需支持类型，0-未知，1-代理支持(关系维护、政策支持、战略合作)，2-客户支持(关系维护、销售政策)，3-市场支持(会议营销、行业地位)，4-策划支持(策略倾向)，5-产品支持(特殊产品、频道内容支持)
 * @apiParam {String} help_comment 所需支持说明
 * @apiParam {Date} close_date 商机关闭时间
 * @apiParam {Number} close_value 商机关闭实际收入
 * @apiParam {String} close_comment 商机关闭备注说明
 * @apiParam {Number} order_money 商机-整体下单金额
 * @apiParam {Number} order_rate 完成率
 * @apiParam {Number} order_rate_real_time 实时完成率
 * @apiParam {Number} video_forecast_money 视频-整体预估投放金额
 * @apiParam {Number} video_order_money 视频-整体下单金额
 * @apiParam {Number} video_order_rate 视频完成率
 * @apiParam {Number} video_forecast_money_remain 视频-整体剩余预估
 * @apiParam {Number} news_forecast_money 新闻-整体预估投放金额
 * @apiParam {Number} news_order_rate 新闻-整体下单金额
 * @apiParam {Number} news_order_money 新闻完成率
 * @apiParam {Number} news_forecast_money_remain 新闻-整体剩余预估
 */

/**
 * @apiDefine       OpportunitySaveResource
 * @apiParamExample 创建商机参数
 * {
 *      "opp_name" : "商机名称", // 必填
 *      "data_from" : "数据来源"
 *      "client_id" : "直接客户 ID"
 *      "short_id" : "客户简称 ID"
 *      "agent_id" : "代理商 ID"
 *      "brand_id" : "品牌产品 ID"
 *      "belong_to" : "归属"
 *      "is_share" : "是否共享"
 *      "owner_rtx" : "所有人"
 *      "sale_rtx" : "销售"
 *      "channel_rtx" : "渠道销售"
 *      "order_date" : "预计签单时间"
 *      "onboard_begin" : "投放开始时间"
 *      "onboard_end" : "预计结束时间"
 *      "forecast_money" : "预估金额"
 *      "forecast_money_remain" : "剩余预估金额"
 *      "step" : "商机阶段"
 *      "probability" : "赢单概率"
 *      "manager_probability" : "主管赢单概率"
 *      "step_comment" : "商机阶段说明"
 *      "risk_type" : "风险类型"
 *      "risk_comment" : "风险说明"
 *      "opp_type" : "商机类型"
 *      "status" : "商机状态"
 *      "is_crucial" : "是否攻坚团队"
 *      "crucial_rtx" : "攻坚团队人员"
 *      "opp_resource" : "商机来源"
 *      "frame_type" : "框架类型"
 *      "help_type" : "支持类型"
 *      "help_comment" : "支持说明"
 *      "close_date" : "关闭时间"
 *      "close_value" : "关闭实际收入"
 *      "close_comment" : "关闭说明"
 *      "order_money" : "下单金额"
 *      "order_rate" : "下单完成率"
 *      "order_rate_real_time" : "实时下单完成率"
 *      "video_forecast_money" : "视频预估金额"
 *      "video_order_money" : "视频下单金额"
 *      "video_order_rate" : "视频下单完成率"
 *      "video_forecast_money_remain : "视频预估剩余金额"
 *      "news_forecast_money" : "新闻预估金额"
 *      "news_order_rate" : "新闻下单完成率"
 *      "news_order_money" : "新闻下单金额"
 *      "news_forecast_money_remain" : "新闻剩余预估金额"
 * }
 */

/**
 * @apiDefine       opportunityUpdateResource
 * @apiParamExample 更新商机参数
 * {
 *      "opp_name" : "商机名称", // 商机名称，必填
 *      "data_from" : "数据来源"
 *      "client_id" : "直接客户 ID"
 *      "short_id" : "客户简称 ID"
 *      "agent_id" : "代理商 ID"
 *      "brand_id" : "品牌产品 ID"
 *      "belong_to" : "归属"
 *      "is_share" : "是否共享"
 *      "owner_rtx" : "所有人"
 *      "sale_rtx" : "销售"
 *      "channel_rtx" : "渠道销售"
 *      "order_date" : "预计签单时间"
 *      "onboard_begin" : "投放开始时间"
 *      "onboard_end" : "预计结束时间"
 *      "forecast_money" : "预估金额"
 *      "forecast_money_remain" : "剩余预估金额"
 *      "step" : "商机阶段"
 *      "probability" : "赢单概率"
 *      "manager_probability" : "主管赢单概率"
 *      "step_comment" : "商机阶段说明"
 *      "risk_type" : "风险类型"
 *      "risk_comment" : "风险说明"
 *      "opp_type" : "商机类型"
 *      "status" : "商机状态"
 *      "is_crucial" : "是否攻坚团队"
 *      "crucial_rtx" : "攻坚团队人员"
 *      "opp_resource" : "商机来源"
 *      "frame_type" : "框架类型"
 *      "help_type" : "支持类型"
 *      "help_comment" : "支持说明"
 *      "close_date" : "关闭时间"
 *      "close_value" : "关闭实际收入"
 *      "close_comment" : "关闭说明"
 *      "order_money" : "下单金额"
 *      "order_rate" : "下单完成率"
 *      "order_rate_real_time" : "实时下单完成率"
 *      "video_forecast_money" : "视频预估金额"
 *      "video_order_money" : "视频下单金额"
 *      "video_order_rate" : "视频下单完成率"
 *      "video_forecast_money_remain" : "视频预估剩余金额"
 *      "news_forecast_money" : "新闻预估金额"
 *      "news_order_rate" : "新闻下单完成率"
 *      "news_order_money" : "新闻下单金额"
 *      "news_forecast_money_remain" : "新闻剩余预估金额"
 * }
 */
class OpportunityHydrator extends Hydrator
{
    protected function getCreateRules()
    {
        return [
            'opportunity_id'              => 'nullable|string|max:36',
            'opp_name'                    => 'required|string|max:300|unique:crm_brand.t_opp,Fopp_name',
            'opp_code'                    => 'nullable|string|max:64',
            'data_from'                   => 'required|integer',
            'client_id'                   => 'required|string|max:36',
            'short_id'                    => 'nullable|string|max:36',
            'agent_id'                    => 'nullable|string|max:36',
            'brand_id'                    => 'required|string|max:36',
            'belong_to'                   => 'integer|in:' . implode(',', array_keys(OpportunityDef::BELONGTO_MAPS)),
            'is_share'                    => 'integer|in:' . implode(',', array_keys(OpportunityDef::ISSHARE_MAPS)),
            'owner_rtx'                   => 'required|string|max:32',
            'sale_rtx'                    => 'nullable|string|max:32',
            'channel_rtx'                 => 'nullable|string|max:32',
            'order_date'                  => 'nullable|date',
            'onboard_begin'               => 'nullable|date',
            'onboard_end'                 => 'nullable|date',
            'forecast_money'              => 'nullable|numeric',
            'forecast_money_remain'       => 'nullable|numeric',
            'step'                        => 'integer|in:' . implode(',', array_keys(OpportunityDef::STEP_MAPS)),
            'probability'                 => 'nullable|numeric',
            'manager_probability'         => 'nullable|numeric',
            'step_comment'                => 'nullable|text',
            'risk_type'                   => 'nullable|integer',
            'risk_comment'                => 'nullable|text',
            'opp_type'                    => 'nullable|integer',
            'status'                      => 'nullable|integer',
            'is_crucial'                  => 'nullable|integer',
            'crucial_rtx'                 => 'nullable|string',
            'opp_resource'                => 'nullable|integer',
            'frame_type'                  => 'nullable|integer',
            'help_type'                   => 'nullable|integer',
            'help_comment'                => 'nullable|string',
            'close_date'                  => 'nullable|date',
            'close_value'                 => 'nullable|numeric',
            'close_comment'               => 'nullable|string',
            'order_money'                 => 'nullable|numeric',
            'order_rate'                  => 'nullable|numeric',
            'order_rate_real_time'        => 'nullable|numeric',
            'video_forecast_money'        => 'nullable|numeric',
            'video_order_money'           => 'nullable|numeric',
            'video_order_rate'            => 'nullable|numeric',
            'video_forecast_money_remain' => 'nullable|numeric',
            'news_forecast_money'         => 'nullable|numeric',
            'news_order_rate'             => 'nullable|numeric',
            'news_order_money'            => 'nullable|numeric',
            'news_forecast_money_remain'  => 'nullable|numeric',
        ];
    }

    protected function getUpdateRules()
    {
        /** @var Opportunity $opportunity */
        $opportunity = $this->model;
        return [
            'opp_name'                    => 'nullable|max:300|unique:crm_brand.t_opp,Fopp_name,' . $opportunity->Fid . ',Fid',
            'opp_code'                    => 'nullable|string|max:64',
            'data_from'                   => 'nullable|integer',
            'client_id'                   => 'nullable|string|max:36',
            'short_id'                    => 'nullable|string|max:36',
            'agent_id'                    => 'nullable|string|max:36',
            'brand_id'                    => 'nullable|string|max:36',
            'belong_to'                   => 'nullable|integer|in:' . implode(',', array_keys(OpportunityDef::BELONGTO_MAPS)),
            'is_share'                    => 'nullable|integer|in:' . implode(',', array_keys(OpportunityDef::ISSHARE_MAPS)),
            'owner_rtx'                   => 'nullable|string|max:32',
            'sale_rtx'                    => 'nullable|string|max:32',
            'channel_rtx'                 => 'nullable|string|max:32',
            'order_date'                  => 'nullable|date',
            'onboard_begin'               => 'nullable|date',
            'onboard_end'                 => 'nullable|date',
            'forecast_money'              => 'nullable|numeric',
            'forecast_money_remain'       => 'nullable|numeric',
            'step'                        => 'nullable|integer|in:' . implode(',', array_keys(OpportunityDef::STEP_MAPS)),
            'probability'                 => 'nullable|numeric',
            'manager_probability'         => 'nullable|numeric',
            'step_comment'                => 'nullable|text',
            'risk_type'                   => 'nullable|integer',
            'risk_comment'                => 'nullable|text',
            'opp_type'                    => 'nullable|integer',
            'status'                      => 'nullable|integer',
            'is_crucial'                  => 'nullable|integer',
            'crucial_rtx'                 => 'nullable|string',
            'opp_resource'                => 'nullable|integer',
            'frame_type'                  => 'nullable|integer',
            'help_type'                   => 'nullable|integer',
            'help_comment'                => 'nullable|string',
            'close_date'                  => 'nullable|date',
            'close_value'                 => 'nullable|numeric',
            'close_comment'               => 'nullable|string',
            'order_money'                 => 'nullable|numeric',
            'order_rate'                  => 'nullable|numeric',
            'order_rate_real_time'        => 'nullable|numeric',
            'video_forecast_money'        => 'nullable|numeric',
            'video_order_money'           => 'nullable|numeric',
            'video_order_rate'            => 'nullable|numeric',
            'video_forecast_money_remain' => 'nullable|numeric',
            'news_forecast_money'         => 'nullable|numeric',
            'news_order_rate'             => 'nullable|numeric',
            'news_order_money'            => 'nullable|numeric',
            'news_forecast_money_remain'  => 'nullable|numeric',
            'is_del'                      => 'nullable|numeric',
        ];
    }

    /**
     * @param array $data
     * @param Model $model
     */
    protected function hydrateForCreate(array $data, Model $model)
    {
        /* @var Opportunity $model */
        if (!empty($data['opportunity_id'])) {
            $model->opportunity_id = $data['opportunity_id'];
        }
        if (!empty($data['opp_code'])) {
            $model->opp_code = $data['opp_code'];
        }
        $model->data_from = $data['data_from'] ?? '';
        $model->opp_name = $data['opp_name'] ?? '';
        $model->client_id = $data['client_id'] ?? '';
        $model->short_id = $data['short_id'] ?? '';
        $model->agent_id = $data['agent_id'] ?? '';
        $model->brand_id = $data['brand_id'] ?? '';
        $model->belong_to = $data['belong_to'] ?? '';
        $model->is_share = $data['is_share'] ?? '';
        $model->owner_rtx = $data['owner_rtx'] ?? '';
        $model->sale_rtx = $data['sale_rtx'] ?? '';
        $model->channel_rtx = $data['channel_rtx'] ?? '';
        $model->order_date = $data['order_date'] ?? '';
        $model->onboard_begin = $data['onboard_begin'] ?? '';
        $model->onboard_end = $data['onboard_end'] ?? '';
        $model->forecast_money = $data['forecast_money'] ?? '';
        $model->forecast_money_remain = $data['forecast_money_remain'] ?? '';
        $model->step = $data['step'] ?? '';
        $model->probability = $data['probability'] ?? '';
        $model->manager_probability = $data['manager_probability'] ?? '';
        $model->step_comment = $data['step_comment'] ?? '';
        $model->risk_type = $data['risk_type'] ?? '';
        $model->risk_comment = $data['risk_comment'] ?? '';
        $model->opp_type = $data['opp_type'] ?? '';
        $model->status = $data['status'] ?? '';
        $model->is_crucial = $data['is_crucial'] ?? '';
        $model->crucial_rtx = $data['crucial_rtx'] ?? '';
        $model->opp_resource = $data['opp_resource'] ?? '';
        $model->frame_type = $data['frame_type'] ?? '';
        $model->help_type = $data['help_type'] ?? '';
        $model->help_comment = $data['help_comment'] ?? '';
        $model->close_date = $data['close_date'] ?? '';
        $model->close_value = $data['close_value'] ?? '';
        $model->close_comment = $data['close_comment'] ?? '';
        $model->order_money = $data['order_money'] ?? '';
        $model->order_rate = $data['order_rate'] ?? '';
        $model->order_rate_real_time = $data['order_rate_real_time'] ?? '';
        $model->video_forecast_money = $data['video_forecast_money'] ?? '';
        $model->video_order_money = $data['video_order_money'] ?? '';
        $model->video_order_rate = $data['video_order_rate'] ?? '';
        $model->video_forecast_money_remain = $data['video_forecast_money_remain'] ?? '';
        $model->news_forecast_money = $data['news_forecast_money'] ?? '';
        $model->news_order_rate = $data['news_order_rate'] ?? '';
        $model->news_order_money = $data['news_order_money'] ?? '';
        $model->news_forecast_money_remain = $data['news_forecast_money_remain'] ?? '';
        $model->save();
        return $model;
    }

    /**
     * @param array $data
     * @param Model $model
     */
    protected function hydrateForUpdate(array $data, Model $model)
    {
        $model->fill($data)->save();
        return $model;
    }
}

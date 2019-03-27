<?php

namespace App\Http\Request;

use App\Repositories\OpportunityDatabaseRepository;
use App\Exceptions\Business\ParamException;

/**
 * Class UpdateOpportunityRequest.
 */
class UpdateOpportunityRequest extends BaseRequest
{
    /**
     * @var Opportunity
     */
    public $opportunity;

    /**
     * @var OpportunityDatabaseRepository
     */
    public $opportunityRepo;

    public function init()
    {
        parent::init();
        $this->opportunityRepo = new OpportunityDatabaseRepository();
    }

    /**
     * @return Opportunity
     */
    public function getOpportunity($opportunityId)
    {
        $opportunity = $this->opportunityRepo->getOneModel(['opportunity_id' => $opportunityId]);
        if (!$opportunity) {
            throw new ParamException('商机 ID 有误', 3901);
        }
        $this->setOpportunity($opportunity);

        return $opportunity;
    }

    /**
     * 基本信息.
     *
     * @return array
     */
    public function getOpportunityParam()
    {
        $res = [];
        $this->has('opp_name') && $res['opp_name'] = filter_str($this->input('opp_name'));
        $this->has('data_from') && $res['data_from'] = (int) $this->input('data_from');
        $this->has('client_id') && $res['client_id'] = $this->input('client_id');
        $this->has('short_id') && $res['short_id'] = $this->input('short_id');
        $this->has('agent_id') && $res['agent_id'] = $this->input('agent_id');
        $this->has('brand_id') && $res['brand_id'] = $this->input('brand_id');
        $this->has('belong_to') && $res['belong_to'] = (int) $this->input('belong_to');
        $this->has('is_share') && $res['is_share'] = (int) $this->input('is_share');
        $this->has('owner_rtx') && $res['owner_rtx'] = $this->input('owner_rtx');
        $this->has('sale_rtx') && $res['sale_rtx'] = $this->input('sale_rtx');
        $this->has('channel_rtx') && $res['channel_rtx'] = $this->input('channel_rtx');
        $this->has('order_date') && $res['order_date'] = $this->input('order_date');
        $this->has('onboard_begin') && $res['onboard_begin'] = $this->input('onboard_begin');
        $this->has('onboard_end') && $res['onboard_end'] = $this->input('onboard_end');
        $this->has('forecast_money') && $res['forecast_money'] = $this->input('forecast_money');
        $this->has('forecast_money_remain') && $res['forecast_money_remain'] = $this->input('forecast_money_remain');
        $this->has('step') && $res['step'] = (int) $this->input('step');
        $this->has('probability') && $res['probability'] = $this->input('probability');
        $this->has('manager_probability') && $res['manager_probability'] = $this->input('manager_probability');
        $this->has('step_comment') && $res['step_comment'] = $this->input('step_comment');
        $this->has('risk_type') && $res['risk_type'] = (int) $this->input('risk_type');
        $this->has('risk_comment') && $res['risk_comment'] = $this->input('risk_comment');
        $this->has('opp_type') && $res['opp_type'] = (int) $this->input('opp_type');
        $this->has('status') && $res['status'] = (int) $this->input('status');
        $this->has('is_crucial') && $res['is_crucial'] = (int) $this->input('is_crucial');
        $this->has('crucial_rtx') && $res['crucial_rtx'] = $this->input('crucial_rtx');
        $this->has('opp_resource') && $res['opp_resource'] = $this->input('opp_resource');
        $this->has('frame_type') && $res['frame_type'] = (int) $this->input('frame_type');
        $this->has('help_type') && $res['help_type'] = (int) $this->input('help_type');
        $this->has('help_comment') && $res['help_comment'] = $this->input('help_comment');
        $this->has('close_date') && $res['close_date'] = $this->input('close_date');
        $this->has('close_value') && $res['close_value'] = $this->input('close_value');
        $this->has('close_comment') && $res['close_comment'] = $this->input('close_comment');
        $this->has('order_money') && $res['order_money'] = $this->input('order_money');
        $this->has('order_rate') && $res['order_rate'] = $this->input('order_rate');
        $this->has('order_rate_real_time') && $res['order_rate_real_time'] = $this->input('order_rate_real_time');
        $this->has('video_forecast_money') && $res['video_forecast_money'] = $this->input('video_forecast_money');
        $this->has('video_order_money') && $res['video_order_money'] = $this->input('video_order_money');
        $this->has('video_order_rate') && $res['video_order_rate'] = $this->input('video_order_rate');
        $this->has('video_forecast_money_remain') && $res['video_forecast_money_remain'] = $this->input('video_forecast_money_remain');
        $this->has('news_forecast_money') && $res['news_forecast_money'] = $this->input('news_forecast_money');
        $this->has('news_order_rate') && $res['news_order_rate'] = $this->input('news_order_rate');
        $this->has('news_order_money') && $res['news_order_money'] = $this->input('news_order_money');
        $this->has('news_forecast_money_remain') && $res['news_forecast_money_remain'] = $this->input('news_forecast_money_remain');

        return $res;
    }

    /**
     * @return array
     */
    public function getForecastParam()
    {
        if ($this->has('forecasts')) {
            return $this->input('forecasts');
        }
    }

    /**
     * @param $opportunity Opportunity
     */
    public function setOpportunity($opportunity)
    {
        $this->opportunity = $opportunity;
    }
}

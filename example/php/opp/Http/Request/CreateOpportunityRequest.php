<?php

namespace App\Http\Request;

/**
 * Class CreateOpportunityRequest.
 */
class CreateOpportunityRequest extends BaseRequest
{
    /**
     * @var Opportunity
     */
    public $opportunity;

    public function rules()
    {
        return [
            'opp_name' => 'required|string|max:300|unique:crm_brand.t_opp,Fopp_name',
            'client_id'=> 'required|string|max:36',
            'brand_id' => 'required|string|max:36',
            'owner_rtx'=> 'required|string|max:32',
        ];
    }

    /**
     * 客户基本信息.
     *
     * @return array
     */
    public function getOpportunityParam()
    {
        return [
            'opp_name'                    => filter_str($this->input('opp_name')),
            'data_from'                   => (int) $this->input('data_from'),
            'client_id'                   => $this->input('client_id'),
            'short_id'                    => $this->input('short_id'),
            'agent_id'                    => $this->input('agent_id'),
            'brand_id'                    => $this->input('brand_id'),
            'belong_to'                   => (int) $this->input('belong_to'),
            'is_share'                    => (int) $this->input('is_share'),
            'owner_rtx'                   => $this->input('owner_rtx'),
            'sale_rtx'                    => $this->input('sale_rtx'),
            'channel_rtx'                 => $this->input('channel_rtx'),
            'order_date'                  => $this->input('order_date'),
            'onboard_begin'               => $this->input('onboard_begin'),
            'onboard_end'                 => $this->input('onboard_end'),
            'forecast_money'              => $this->input('forecast_money'),
            'forecast_money_remain'       => $this->input('forecast_money_remain'),
            'step'                        => (int) $this->input('step'),
            'probability'                 => $this->input('probability'),
            'manager_probability'         => $this->input('manager_probability'),
            'step_comment'                => $this->input('step_comment'),
            'risk_type'                   => (int) $this->input('risk_type'),
            'risk_comment'                => $this->input('risk_comment'),
            'opp_type'                    => (int) $this->input('opp_type'),
            'status'                      => (int) $this->input('status'),
            'is_crucial'                  => (int) $this->input('is_crucial'),
            'crucial_rtx'                 => $this->input('crucial_rtx'),
            'opp_resource'                => $this->input('opp_resource'),
            'frame_type'                  => (int) $this->input('frame_type'),
            'help_type'                   => (int) $this->input('help_type'),
            'help_comment'                => $this->input('help_comment'),
            'close_date'                  => $this->input('close_date'),
            'close_value'                 => $this->input('close_value'),
            'close_comment'               => $this->input('close_comment'),
            'order_money'                 => $this->input('order_money'),
            'order_rate'                  => $this->input('order_rate'),
            'order_rate_real_time'        => $this->input('order_rate_real_time'),
            'video_forecast_money'        => $this->input('video_forecast_money'),
            'video_order_money'           => $this->input('video_order_money'),
            'video_order_rate'            => $this->input('video_order_rate'),
            'video_forecast_money_remain' => $this->input('video_forecast_money_remain'),
            'news_forecast_money'         => $this->input('news_forecast_money'),
            'news_order_rate'             => $this->input('news_order_rate'),
            'news_order_money'            => $this->input('news_order_money'),
            'news_forecast_money_remain'  => $this->input('news_forecast_money_remain'),
        ];
    }

    /**
     * @return array
     */
    public function getForecastParam()
    {
        // return [
        //     'opportunity_id' => $this->opportunity->opportunity_id,
        //     'forecasts' => $this->input('forecasts', []),
        // ];
        return $this->input('forecasts', []);
    }

    /**
     * @param $opportunity Opportunity
     */
    public function setOpportunity($opportunity)
    {
        $this->opportunity = $opportunity;
    }
}

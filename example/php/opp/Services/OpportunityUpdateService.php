<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Forecast;
use App\Models\Detail;
use App\Http\Request\UpdateOpportunityRequest;
use App\Exceptions\Business\BusinessException;

/**
 * Class OpportunityUpdateService.
 *
 * @author hubertchen <hubertchen@tencent.com>
 */
class OpportunityUpdateService
{
    /**
     * 更新商机、预估、预估详情.
     *
     * @param UpdateOpportunityRequest $request
     */
    public function update(UpdateOpportunityRequest $request, $opportunityId)
    {
        $opportunity = $request->getOpportunity($opportunityId);
        // 读取商机基础信息相关参数
        $basicParams = $request->getOpportunityParam();
        // 检查商机名称是否已存在
        if (isset($basicParams['opp_name']) && Opportunity::nameExist($basicParams['opp_name'], $opportunityId)) {
            throw new BusinessException('商机名称已存在');
        };
        // 读取商机预估相关参数 更新商机预估
        $this->updateForecast($request->getForecastParam(), $opportunity);
        // 更新商机基础信息
        if (!empty($basicParams)) {
            $opportunity = (new Opportunity())->updateRow($basicParams, $opportunity);
        }
    }

    /**
     * 更新商机预估.
     *
     * @param [type]      $paramForecasts
     * @param Opportunity $opportunity
     */
    private function updateForecast($paramForecasts, Opportunity $opportunity)
    {
        //todo. 旧商机业务没有传递原预估id，无法比对【待更新】【待删除】【待新建】，需要联系前端一起重构更新预估的逻辑
        $this->deleteForecasts($opportunity);
        $this->createForecasts($paramForecasts, $opportunity);
    }

    /**
     * 删除指定的预估.
     *
     * @param Opportunity $opportunity
     */
    private function deleteForecasts(Opportunity $opportunity)
    {
        $forecastIds = Forecast::getForecasts($opportunity)->pluck('Fforecast_id')->toArray();
        Forecast::softDeleteByIds(array_wrap($forecastIds));
        $this->deleteDetails($forecastIds);
    }

    /**
     * 删除预估关联的预估详情.
     *
     * @param array $forecastIds
     */
    private function deleteDetails($forecastIds)
    {
        Detail::softDeleteByIds(array_wrap($forecastIds));
    }

    /**
     * @param $paramForecasts
     * @param Opportunity $opportunity
     *
     * @return Forecast[]
     */
    private function createForecasts($paramForecasts, Opportunity $opportunity)
    {
        $res = [];
        if (empty($paramForecasts)) {
            return $res;
        }

        foreach ($paramForecasts as $paramForecast) {
            $param = [
                'opportunity_id'               => $paramForecast['opportunity_id'] ?? $opportunity->opportunity_id,
                'forecast_id'                  => $paramForecast['forecast_id'] ?? '',
                'year'                         => $paramForecast['year'] ?? '',
                'q'                            => $paramForecast['q'] ?? '',
                'forecast_money'               => $paramForecast['forecast_money'] ?? '',
                'forecast_money_remain'        => $paramForecast['forecast_money_remain'] ?? '',
                'order_money'                  => $paramForecast['order_money'] ?? '',
                'order_rate'                   => $paramForecast['order_rate'] ?? '',
                'video_forecast_money'         => $paramForecast['video_forecast_money'] ?? '',
                'video_order_money'            => $paramForecast['video_order_money'] ?? '',
                'video_order_rate'             => $paramForecast['video_order_rate'] ?? '',
                'video_forecast_money_remain'  => $paramForecast['video_forecast_money_remain'] ?? '',
                'news_forecast_money'          => $paramForecast['news_forecast_money'] ?? '',
                'news_order_rate'              => $paramForecast['news_order_rate'] ?? '',
                'news_order_money'             => $paramForecast['news_order_money'] ?? '',
                'news_forecast_money_remain'   => $paramForecast['news_forecast_money_remain'] ?? '',
                'begin'                        => $paramForecast['begin'] ?? '',
                'end'                          => $paramForecast['end'] ?? '',
            ];
            $forecast = (new Forecast())->insertRow($param);

            if (isset($paramForecast['details'])) {
                $forecast['details'] = $this->createDetails($paramForecast['details'], $forecast);
            }

            $res[] = $forecast;
        }

        return $res;
    }

    /**
     * @param $paramDetails
     * @param Forecast $forecast
     *
     * @return Detail[]
     */
    private function createDetails($paramDetails, Forecast $forecast)
    {
        $res = [];
        if (empty($paramDetails)) {
            return [];
        }

        foreach ($paramDetails as $paramDetail) {
            $param = [
                'opportunity_id'      => $paramDetail['opportunity_id'] ?? $forecast->opportunity_id,
                'forecast_id'         => $paramDetail['forecast_id'] ?? $forecast->forecast_id,
                'year'                => $paramDetail['year'] ?? $forecast->year,
                'q'                   => $paramDetail['q'] ?? $forecast->q,
                'forecast_money'      => $paramDetail['forecast_money'] ?? '',
                'platform'            => $paramDetail['platform'] ?? '',
                'cooperation_type'    => $paramDetail['cooperation_type'] ?? '',
                'business_project_id' => $paramDetail['business_project_id'] ?? '',
                'business_project'    => $paramDetail['business_project'] ?? '',
                'ad_product_id'       => $paramDetail['ad_product_id'] ?? '',
                'ad_product'          => $paramDetail['ad_product'] ?? '',
                'resource_id'         => $paramDetail['resource_id'] ?? '',
                'resource_name'       => $paramDetail['resource_name'] ?? '',
                'other_resource'      => $paramDetail['other_resource'] ?? '',
                'play_type'           => $paramDetail['play_type'] ?? '',
                'play_type_id'        => $paramDetail['play_type_id'] ?? '',
            ];
            $res[] = (new Detail())->insertRow($param);
        }

        return $res;
    }
}

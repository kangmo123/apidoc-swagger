<?php

namespace App\Http\Resources;

use App\Models\Forecast;
use Illuminate\Http\Request;

/**
 * @apiDefine ForecastItemResource
 * @apiSuccessExample 返回的商机预估资源
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 *  "data":
 *      {
 *           "opportunity_id": "8A01D402-A309-11E5-98ED-6CAE8B22C292",
 *           "forecast_id": "8A4DD492-A309-11E5-98ED-6CAE8B22C292",
 *           "year": 2015,
 *           "q": 4,
 *           "forecast_money": "50000",
 *           "forecast_money_remain": "50000",
 *           "order_money": "0",
 *           "order_rate": "0.00",
 *           "video_forecast_money": "0",
 *           "video_order_money": "0",
 *           "video_order_rate": "0.00",
 *           "video_forecast_money_remain": "0",
 *           "news_forecast_money": "0",
 *           "news_order_rate": "0.00",
 *           "news_order_money": "0",
 *           "news_forecast_money_remain": "0",
 *           "begin": "0000-00-00",
 *           "end": "0000-00-00",
 *           "is_del": 0,
 *           "created_by": "",
 *           "created_at": "2015-12-15 08:55:07",
 *           "updated_by": "",
 *           "updated_at": "2017-11-29 05:40:19"
 *       },
 * }
 */

/**
 * @apiDefine ForecastCollectionResource
 * @apiSuccessExample 返回的商机预估集合资源
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 *  "data": [
 *      {
 *           "opportunity_id": "8A01D402-A309-11E5-98ED-6CAE8B22C292",
 *           "forecast_id": "8A4DD492-A309-11E5-98ED-6CAE8B22C292",
 *           "year": 2015,
 *           "q": 4,
 *           "forecast_money": "50000",
 *           "forecast_money_remain": "50000",
 *           "order_money": "0",
 *           "order_rate": "0.00",
 *           "video_forecast_money": "0",
 *           "video_order_money": "0",
 *           "video_order_rate": "0.00",
 *           "video_forecast_money_remain": "0",
 *           "news_forecast_money": "0",
 *           "news_order_rate": "0.00",
 *           "news_order_money": "0",
 *           "news_forecast_money_remain": "0",
 *           "begin": "0000-00-00",
 *           "end": "0000-00-00",
 *           "is_del": 0,
 *           "created_by": "",
 *           "created_at": "2015-12-15 08:55:07",
 *           "updated_by": "",
 *           "updated_at": "2017-11-29 05:40:19"
 *       },
 *  ],
 * }
 */
class ForecastResource extends Resource
{
    /**
     * toArray
     *
     * @param Request $request
     * @return void
     */
    public function toArray($request)
    {
        /**
         * @var Forecast $forecast
         */
        $forecast                = $this;
        return [
            'opportunity_id'                    => $forecast->Fopportunity_id,
            'forecast_id'                       => $forecast->Fforecast_id,
            'year'                              => $forecast->Fyear,
            'q'                                 => $forecast->Fq,
            'forecast_money'                    => $forecast->Fforecast_money,
            'forecast_money_remain'             => $forecast->Fforecast_money_remain,
            'order_money'                       => $forecast->Forder_money,
            'order_rate'                        => $forecast->Forder_rate,
            'video_forecast_money'              => $forecast->Fvideo_forecast_money,
            'video_order_money'                 => $forecast->Fvideo_order_money,
            'video_order_rate'                  => $forecast->Fvideo_order_rate,
            'video_forecast_money_remain'       => $forecast->Fvideo_forecast_money_remain,
            'news_forecast_money'               => $forecast->Fnews_forecast_money,
            'news_order_rate'                   => $forecast->Fnews_order_rate,
            'news_order_money'                  => $forecast->Fnews_order_money,
            'news_forecast_money_remain'        => $forecast->Fnews_forecast_money_remain,
            'begin'                             => $forecast->Fbegin,
            'end'                               => $forecast->Fend,
            'is_del'                            => $forecast->Fis_del,
            'created_by'                        => $forecast->Fcreated_by,
            'created_at'                        => $forecast->Fcreated_at->toDateTimeString(),
            'updated_by'                        => $forecast->Fupdated_by,
            'updated_at'                        => $forecast->Fupdated_at->toDateTimeString(),
        ];
    }
}

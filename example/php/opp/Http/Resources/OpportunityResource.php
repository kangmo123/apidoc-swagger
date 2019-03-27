<?php

namespace App\Http\Resources;

use App\Models\Opportunity;
use Illuminate\Http\Request;

/**
 * @apiDefine OpportunityItemResource
 * @apiSuccessExample 返回的商机资源
 * HTTP/1.1 200 OK
 * {
 *    "data": {
 *         "id": 164633,
 *         "opportunity_id": "6A0F2646-FDD3-213E-447F-E5AE01A6F757",
 *         "opp_code": "OPP20181128154054870",
 *         "data_from": 2,
 *         "opp_name": "西安百事可乐饮料有限公司1128",
 *         "client_id": "AEAD281E-03C3-E511-97F5-00151792B3AC",
 *         "short_id": "0A310020-69C2-E511-97F5-00151792B3AC",
 *         "agent_id": "84445605-69C2-E511-97F5-00151792B3AC",
 *         "brand_id": "D6F65039-03C3-E511-97F5-00151792B3AC",
 *         "belong_to": 1,
 *         "is_share": 0,
 *         "owner_rtx": "akiefeng",
 *         "sale_rtx": "akiefeng",
 *         "channel_rtx": "qqchaozhang",
 *         "order_date": "2018-10-17",
 *         "onboard_begin": "2018-11-28",
 *         "onboard_end": "2018-12-28",
 *         "forecast_money": "100000.00000000",
 *         "forecast_money_remain": "100000.00000000",
 *         "step": 5,
 *         "probability": 61,
 *         "manager_probability": -1,
 *         "step_comment": " ",
 *         "risk_type": 0,
 *         "risk_comment": "",
 *         "opp_type": 1,
 *         "status": 0,
 *         "is_crucial": 0,
 *         "crucial_rtx": "",
 *         "opp_resource": 0,
 *         "frame_type": 0,
 *         "help_type": 0,
 *         "help_comment": "",
 *         "close_date": "2999-01-01",
 *         "close_value": "0.00000000",
 *         "close_comment": "",
 *         "order_money": "0",
 *         "order_rate": "0.00",
 *         "order_rate_real_time": "0.00",
 *         "video_forecast_money": "100000",
 *         "video_order_money": "0",
 *         "video_order_rate": "0.00",
 *         "video_forecast_money_remain": "100000",
 *         "news_forecast_money": "0",
 *         "news_order_rate": "0.00",
 *         "news_order_money": "0",
 *         "news_forecast_money_remain": "0",
 *         "forecasts": [
 *             {
 *                    "forecast_id": "A2B45F76-B7FB-BE71-0B66-5D121C20D455",
 *                    "year": 2018,
 *                    "q": 4,
 *                    "forecast_money": "100000",
 *                    "forecast_money_remain": "100000",
 *                    "order_money": "0",
 *                    "order_rate": "0.00",
 *                    "video_forecast_money": "100000",
 *                    "video_order_money": "0",
 *                    "video_order_rate": "0.00",
 *                    "video_forecast_money_remain": "100000",
 *                    "news_forecast_money": "0",
 *                    "news_order_rate": "0.00",
 *                    "news_order_money": "0",
 *                    "news_forecast_money_remain": "0",
 *                    "begin": "2018-11-28",
 *                    "end": "2018-12-28",
 *                    "details": [
 *                        {
 *                               "forecast_id": "A2B45F76-B7FB-BE71-0B66-5D121C20D455",
 *                               "forecast_detail_id": "C29CC5F3-552A-479A-CE0F-CFBD8C627946",
 *                               "year": 2018,
 *                               "q": 4,
 *                               "forecast_money": "100000",
 *                               "platform": 101,
 *                               "cooperation_type": 1,
 *                               "business_project_id": 2018112600010,
 *                               "business_project": "测试22",
 *                               "ad_product_id": 0,
 *                               "ad_product": "",
 *                               "resource_id": 0,
 *                               "resource_name": "",
 *                               "other_resource": "",
 *                               "play_type": "",
 *                               "play_type_id": 0,
 *                               "created_by": "yanmeiliu",
 *                               "created_at": "2018-11-28 15:40:54",
 *                               "updated_by": "yanmeiliu",
 *                               "updated_at": "2018-11-28 15:40:54"
 *                        }
 *                    ]
 *             }
 *         ],
 *         "processes": [
 *             {
 *                   "opp_process_id": "8DA06C25-1116-D3F6-3341-7DB719D3E314",
 *                   "step": 2,
 *                   "probability": 1,
 *                   "manager_probability": -1,
 *                   "comment": " ",
 *                   "created_by": "yoyosjzhang",
 *                   "created_at": "2018-11-28 15:40:54",
 *                   "updated_by": "yoyosjzhang",
 *                   "updated_at": "2018-11-28 15:40:54"
 *             },
 *             {
 *                   "opp_process_id": "B31F0070-DF21-8819-A694-AC5C633FE78A",
 *                   "step": 5,
 *                   "probability": 61,
 *                   "manager_probability": -1,
 *                   "comment": " ",
 *                   "created_by": "yanmeiliu",
 *                   "created_at": "2018-11-29 15:31:00",
 *                   "updated_by": "yanmeiliu",
 *                   "updated_at": "2018-11-29 15:31:00"
 *             },
 *             {
 *                   "opp_process_id": "05171D1B-EDC4-2854-019D-4A187671E6A1",
 *                   "step": 6,
 *                   "probability": 100,
 *                   "manager_probability": -1,
 *                   "comment": " ",
 *                   "created_by": "apigateway",
 *                   "created_at": "2018-11-29 16:05:03",
 *                   "updated_by": "apigateway",
 *                   "updated_at": "2018-11-29 16:05:03"
 *             },
 *             {
 *                   "opp_process_id": "5BAEAD71-F593-BE5C-EF64-8C4EDFAB7990",
 *                   "step": 5,
 *                   "probability": 61,
 *                   "manager_probability": -1,
 *                   "comment": " ",
 *                   "created_by": "apigateway",
 *                   "created_at": "2018-11-29 16:06:25",
 *                   "updated_by": "apigateway",
 *                   "updated_at": "2018-11-29 16:06:25"
 *             }
 *         ],
 *         "created_by": "yoyosjzhang",
 *         "created_at": "2018-11-28 15:40:54",
 *         "updated_by": "hubertchen",
 *         "updated_at": "2018-11-29 17:32:58"
 *   },
 *   "code": 0,
 *   "msg": "OK"
 * }
 */

/**
 * @apiDefine OpportunityCollectionResource
 * @apiSuccessExample 返回的商机集合资源
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 *  "data": [
 * {
 *        "id": 164633,
 *        "opportunity_id": "6A0F2646-FDD3-213E-447F-E5AE01A6F757",
 *        "opp_code": "OPP20181128154054870",
 *        "data_from": 2,
 *        "opp_name": "西安百事可乐饮料有限公司1128",
 *        "client_id": "AEAD281E-03C3-E511-97F5-00151792B3AC",
 *        "short_id": "0A310020-69C2-E511-97F5-00151792B3AC",
 *        "agent_id": "84445605-69C2-E511-97F5-00151792B3AC",
 *        "brand_id": "D6F65039-03C3-E511-97F5-00151792B3AC",
 *        "belong_to": 1,
 *        "is_share": 0,
 *        "owner_rtx": "akiefeng",
 *        "sale_rtx": "akiefeng",
 *        "channel_rtx": "qqchaozhang",
 *        "order_date": "2018-10-17",
 *        "onboard_begin": "2018-11-28",
 *        "onboard_end": "2018-12-28",
 *        "forecast_money": "100000.00000000",
 *        "forecast_money_remain": "100000.00000000",
 *        "step": 5,
 *        "probability": 61,
 *        "manager_probability": -1,
 *        "step_comment": " ",
 *        "risk_type": 0,
 *        "risk_comment": "",
 *        "opp_type": 1,
 *        "status": 0,
 *        "is_crucial": 0,
 *        "crucial_rtx": "",
 *        "opp_resource": 0,
 *        "frame_type": 0,
 *        "help_type": 0,
 *        "help_comment": "",
 *        "close_date": "2999-01-01",
 *        "close_value": "0.00000000",
 *        "close_comment": "",
 *        "order_money": "0",
 *        "order_rate": "0.00",
 *        "order_rate_real_time": "0.00",
 *        "video_forecast_money": "100000",
 *        "video_order_money": "0",
 *        "video_order_rate": "0.00",
 *        "video_forecast_money_remain": "100000",
 *        "news_forecast_money": "0",
 *        "news_order_rate": "0.00",
 *        "news_order_money": "0",
 *        "news_forecast_money_remain": "0",
 *        "forecasts": [
 *           {
 *                "forecast_id": "A2B45F76-B7FB-BE71-0B66-5D121C20D455",
 *                "year": 2018,
 *                "q": 4,
 *                "forecast_money": "100000",
 *                "forecast_money_remain": "100000",
 *                "order_money": "0",
 *                "order_rate": "0.00",
 *                "video_forecast_money": "100000",
 *                "video_order_money": "0",
 *                "video_order_rate": "0.00",
 *                "video_forecast_money_remain": "100000",
 *                "news_forecast_money": "0",
 *                "news_order_rate": "0.00",
 *                "news_order_money": "0",
 *                "news_forecast_money_remain": "0",
 *                "begin": "2018-11-28",
 *                "end": "2018-12-28",
 *                "details": [
                    {
 *                        "forecast_id": "A2B45F76-B7FB-BE71-0B66-5D121C20D455",
 *                        "forecast_detail_id": "C29CC5F3-552A-479A-CE0F-CFBD8C627946",
 *                        "year": 2018,
 *                        "q": 4,
 *                        "forecast_money": "100000",
 *                        "platform": 101,
 *                        "cooperation_type": 1,
 *                        "business_project_id": 2018112600010,
 *                        "business_project": "测试22",
 *                        "ad_product_id": 0,
 *                        "ad_product": "",
 *                        "resource_id": 0,
 *                        "resource_name": "",
 *                        "other_resource": "",
 *                        "play_type": "",
 *                        "play_type_id": 0,
 *                        "created_by": "yanmeiliu",
 *                        "created_at": "2018-11-28 15:40:54",
 *                        "updated_by": "yanmeiliu",
 *                        "updated_at": "2018-11-28 15:40:54"
 *                   }
 *               ]
 *           }
 *       ],
 *        "processes": [
 *          {
 *                "opp_process_id": "8DA06C25-1116-D3F6-3341-7DB719D3E314",
 *                "step": 2,
 *                "probability": 1,
 *                "manager_probability": -1,
 *                "comment": " ",
 *                "created_by": "yoyosjzhang",
 *                "created_at": "2018-11-28 15:40:54",
 *                "updated_by": "yoyosjzhang",
 *                "updated_at": "2018-11-28 15:40:54"
 *          },
 *          {
 *                "opp_process_id": "B31F0070-DF21-8819-A694-AC5C633FE78A",
 *                "step": 5,
 *                "probability": 61,
 *                "manager_probability": -1,
 *                "comment": " ",
 *                "created_by": "yanmeiliu",
 *                "created_at": "2018-11-29 15:31:00",
 *                "updated_by": "yanmeiliu",
 *                "updated_at": "2018-11-29 15:31:00"
 *           }
 *      ],
 *        "created_by": "yoyosjzhang",
 *        "created_at": "2018-11-28 15:40:54",
 *        "updated_by": "hubertchen",
 *        "updated_at": "2018-11-29 17:32:58"
 *   },
 *  ],
 *  "page_info": {
 *      "page": 1,
 *      "per_page": 20,
 *      "total_page": 1,
 *      "total_number": 2
 *  }
 * }
 */
class OpportunityResource extends Resource
{
    /**
     * toArray.
     *
     * @param Request $request
     */
    public function toArray($request)
    {
        /**
         * @var Opportunity
         */
        $opportunity = $this;
        $forecasts = $this->forecasts;
        $processes = $this->processes;
        return [
            'id'                          => $this->id,
            'opportunity_id'              => $this->opportunity_id,
            'opp_code'                    => $this->opp_code,
            'data_from'                   => $this->data_from,
            'opp_name'                    => $this->opp_name,
            'client_id'                   => $this->client_id,
            'short_id'                    => $this->short_id,
            'agent_id'                    => $this->agent_id,
            'brand_id'                    => $this->brand_id,
            'belong_to'                   => $this->belong_to,
            'is_share'                    => $this->is_share,
            'owner_rtx'                   => $this->owner_rtx,
            'sale_rtx'                    => $this->sale_rtx,
            'channel_rtx'                 => $this->channel_rtx,
            'order_date'                  => $this->order_date,
            'onboard_begin'               => $this->onboard_begin,
            'onboard_end'                 => $this->onboard_end,
            'forecast_money'              => $this->forecast_money,
            'forecast_money_remain'       => $this->forecast_money_remain,
            'step'                        => $this->step,
            'probability'                 => $this->probability,
            'manager_probability'         => $this->manager_probability,
            'step_comment'                => $this->step_comment,
            'risk_type'                   => $this->risk_type,
            'risk_comment'                => $this->risk_comment,
            'opp_type'                    => $this->opp_type,
            'status'                      => $this->status,
            'is_crucial'                  => $this->is_crucial,
            'crucial_rtx'                 => $this->crucial_rtx,
            'opp_resource'                => $this->opp_resource,
            'frame_type'                  => $this->frame_type,
            'help_type'                   => $this->help_type,
            'help_comment'                => $this->help_comment,
            'close_date'                  => $this->close_date,
            'close_value'                 => $this->close_value,
            'close_comment'               => $this->close_comment,
            'order_money'                 => $this->order_money,
            'order_rate'                  => $this->order_rate,
            'order_rate_real_time'        => $this->order_rate_real_time,
            'video_forecast_money'        => $this->video_forecast_money,
            'video_order_money'           => $this->video_order_money,
            'video_order_rate'            => $this->video_order_rate,
            'video_forecast_money_remain' => $this->video_forecast_money_remain,
            'news_forecast_money'         => $this->news_forecast_money,
            'news_order_rate'             => $this->news_order_rate,
            'news_order_money'            => $this->news_order_money,
            'news_forecast_money_remain'  => $this->news_forecast_money_remain,
            'forecasts'                   => $this->when(!empty($forecasts), function () use ($forecasts) {
                $forecastsArr = [];
                foreach ($forecasts as $forecast) {
                    $details = $forecast->details;
                    $forecastsArr[] = [
                        'forecast_id'                => $forecast->forecast_id,
                        'year'                       => $forecast->year,
                        'q'                          => $forecast->q,
                        'forecast_money'             => $forecast->forecast_money,
                        'forecast_money_remain'      => $forecast->forecast_money_remain,
                        'order_money'                => $forecast->order_money,
                        'order_rate'                 => $forecast->order_rate,
                        'video_forecast_money'       => $forecast->video_forecast_money,
                        'video_order_money'          => $forecast->video_order_money,
                        'video_order_rate'           => $forecast->video_order_rate,
                        'video_forecast_money_remain'=> $forecast->video_forecast_money_remain,
                        'news_forecast_money'        => $forecast->news_forecast_money,
                        'news_order_rate'            => $forecast->news_order_rate,
                        'news_order_money'           => $forecast->news_order_money,
                        'news_forecast_money_remain' => $forecast->news_forecast_money_remain,
                        'begin'                      => $forecast->begin,
                        'end'                        => $forecast->end,
                        'details'                    => $this->when(!empty($details), function () use ($details) {
                            $detailsArr = [];
                            foreach ($details as $detail) {
                                $detailsArr[] = [
                                    'forecast_id'                => $detail->forecast_id,
                                    'forecast_id'                => $detail->forecast_id,
                                    'forecast_detail_id'         => $detail->forecast_detail_id,
                                    'year'                       => $detail->year,
                                    'q'                          => $detail->q,
                                    'forecast_money'             => $detail->forecast_money,
                                    'platform'                   => $detail->platform,
                                    'cooperation_type'           => $detail->cooperation_type,
                                    'business_project_id'        => $detail->business_project_id,
                                    'business_project'           => $detail->business_project,
                                    'ad_product_id'              => $detail->ad_product_id,
                                    'ad_product'                 => $detail->ad_product,
                                    'resource_id'                => $detail->resource_id,
                                    'resource_name'              => $detail->resource_name,
                                    'other_resource'             => $detail->other_resource,
                                    'play_type'                  => $detail->play_type,
                                    'play_type_id'               => $detail->play_type_id,
                                    'created_by'                 => $detail->created_by,
                                    'created_at'                 => $detail->created_at->toDateTimeString(),
                                    'updated_by'                 => $detail->updated_by,
                                    'updated_at'                 => $detail->updated_at->toDateTimeString(),
                                ];
                            }
                            return $detailsArr;
                        }),
                    ];
                }
                return $forecastsArr;
            }),
            'processes' => $this->when(!empty($processes), function () use ($processes) {
                $processesArr = [];
                foreach ($processes as $process) {
                    $processesArr[] = [
                        'opp_process_id'               => $process->opp_process_id,
                        'step'                         => $process->step,
                        'probability'                  => $process->probability,
                        'manager_probability'          => $process->manager_probability,
                        'comment'                      => $process->comment,
                        'created_by'                   => $process->created_by,
                        'created_at'                   => $process->created_at->toDateTimeString(),
                        'updated_by'                   => $process->updated_by,
                        'updated_at'                   => $process->updated_at->toDateTimeString(),
                    ];
                }
                return $processesArr;
            }),
            'created_by' => $opportunity->created_by,
            'created_at' => $opportunity->created_at->toDateTimeString(),
            'updated_by' => $opportunity->updated_by,
            'updated_at' => $opportunity->updated_at->toDateTimeString(),
        ];
    }
}

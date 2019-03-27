<?php

namespace App\Http\Hydrators;

use App\Models\Model;
use App\Models\Forecast;
use App\Models\Opportunity;

/**
 * @apiDefine       CreateForecastParams
 * @apiParam {String} opportunity_id        商机ID，必填
 * @apiParam {Number} year                  预估年份
 * @apiParam {Number} q                     预估季度
 * @apiParam {Number} forecast_money        整体预估金额
 * @apiParam {Number} forecast_money_remain 剩余预估金额
 * @apiParam {Number} order_money           下单金额
 * @apiParam {Number} order_rate            下单完成率
 * @apiParam {Number} video_forecast_money  视频预估金额
 * @apiParam {Number} video_order_money     视频下单金额
 * @apiParam {Number} video_order_rate      视频下单完成率
 * @apiParam {Number} video_forecast_money_remain  视频剩余预估
 * @apiParam {Number} news_forecast_money   新闻预估金额
 * @apiParam {Number} news_order_rate       新闻下单完成率
 * @apiParam {Number} news_order_money      新闻下单金额
 * @apiParam {Number} news_forecast_money_remain  新闻剩余预估
 * @apiParam {Date} begin                 预估投放的开始日期
 * @apiParam {Date} end                   预估投放的结束日期
 */

/**
 * @apiDefine       UpdateForecastParams
 * @apiParam {String} forecast_id        商机预估 ID，必填
 * @apiParam {Number} year                  预估年份
 * @apiParam {Number} q                     预估季度
 * @apiParam {Number} forecast_money        整体预估金额
 * @apiParam {Number} forecast_money_remain 剩余预估金额
 * @apiParam {Number} order_money           下单金额
 * @apiParam {Number} order_rate            下单完成率
 * @apiParam {Number} video_forecast_money  视频预估金额
 * @apiParam {Number} video_order_money     视频下单金额
 * @apiParam {Number} video_order_rate      视频下单完成率
 * @apiParam {Number} video_forecast_money_remain  视频剩余预估
 * @apiParam {Number} news_forecast_money   新闻预估金额
 * @apiParam {Number} news_order_rate       新闻下单完成率
 * @apiParam {Number} news_order_money      新闻下单金额
 * @apiParam {Number} news_forecast_money_remain  新闻剩余预估
 * @apiParam {Date} begin                 预估投放的开始日期
 * @apiParam {Date} end                   预估投放的结束日期
 */

/**
 * @apiDefine       ForecastCreateResource
 * @apiParamExample 创建商机预估参数
 * {
 *      'opportunity_id'       : '商机ID，编辑时必填',
 *      'year'                 : '预估年份',
 *      'q'                    : '预估季度',
 *      'forecast_money'       : '整体预估金额',
 *      'forecast_money_remain': '剩余预估金额',
 *      'order_money'          : '下单金额',
 *      'order_rate'           : '下单完成率',
 *      'video_forecast_money' : '视频预估金额',
 *      'video_order_money'    : '视频下单金额',
 *      'video_order_rate'     : '视频下单完成率',
 *      'video_forecast_money_remain' : '视频剩余预估',
 *      'news_forecast_money'  : '新闻预估金额',
 *      'news_order_rate'      : '新闻下单完成率',
 *      'news_order_money'     : '新闻下单金额',
 *      'news_forecast_money_remain' : '新闻剩余预估',
 *      'begin'                : '预估投放的开始日期',
 *      'end'                  : '预估投放的结束日期',
 * }
 */

/**
 * @apiDefine       forecastUpdateResource
 * @apiParamExample 更新商机预估参数
 * {
 *      'forecast_id'          : '预估 ID',
 *      'year'                 : '预估年份',
 *      'q'                    : '预估季度',
 *      'forecast_money'       : '整体预估金额',
 *      'forecast_money_remain': '剩余预估金额',
 *      'order_money'          : '下单金额',
 *      'order_rate'           : '下单完成率',
 *      'video_forecast_money' : '视频预估金额',
 *      'video_order_money'    : '视频下单金额',
 *      'video_order_rate'     : '视频下单完成率',
 *      'video_forecast_money_remain' : '视频剩余预估',
 *      'news_forecast_money'  : '新闻预估金额',
 *      'news_order_rate'      : '新闻下单完成率',
 *      'news_order_money'     : '新闻下单金额',
 *      'news_forecast_money_remain' : '新闻剩余预估',
 *      'begin'                : '预估投放的开始日期',
 *      'end'                  : '预估投放的结束日期',
 * }
 */
class ForecastHydrator extends Hydrator
{
    protected function getCreateRules()
    {
        return [
            'opportunity_id'             => 'nullable|string|max:36',
            'forecast_id'                => 'nullable|string|max:36',
            'year'                       => 'required|integer',
            'q'                          => 'required|integer',
            'forecast_money'             => 'required|numeric',
            'forecast_money_remain'      => 'nullable|numeric',
            'order_money'                => 'nullable|numeric',
            'order_rate'                 => 'nullable|numeric',
            'video_forecast_money'       => 'nullable|numeric',
            'video_order_money'          => 'nullable|numeric',
            'video_order_rate'           => 'nullable|numeric',
            'video_forecast_money_remain'=> 'nullable|numeric',
            'news_forecast_money'        => 'nullable|numeric',
            'news_order_rate'            => 'nullable|numeric',
            'news_order_money'           => 'nullable|numeric',
            'news_forecast_money_remain' => 'nullable|numeric',
            'begin'                      => 'date',
            'end'                        => 'date',
        ];
    }

    protected function getUpdateRules()
    {
        /** @var Forecast $forecast */
        $forecast = $this->model;
        return [
            'opportunity_id'             => 'nullable|string|max:36',
            'forecast_id'                => 'nullable|string|max:36',
            'year'                       => 'required|integer',
            'q'                          => 'required|integer',
            'forecast_money'             => 'required|numeric',
            'forecast_money_remain'      => 'nullable|numeric',
            'order_money'                => 'nullable|numeric',
            'order_rate'                 => 'nullable|numeric',
            'video_forecast_money'       => 'nullable|numeric',
            'video_order_money'          => 'nullable|numeric',
            'video_order_rate'           => 'nullable|numeric',
            'video_forecast_money_remain'=> 'nullable|numeric',
            'news_forecast_money'        => 'nullable|numeric',
            'news_order_rate'            => 'nullable|numeric',
            'news_order_money'           => 'nullable|numeric',
            'news_forecast_money_remain' => 'nullable|numeric',
            'begin'                      => 'date',
            'end'                        => 'date',
        ];
    }

    protected function hydrateForCreate(array $data, Model $model)
    {
        if (isset($data['forecast_id'])) {
            $forecast = Forecast::query()->where('forecast_id', $data['forecast_id'])->first();
            if ($forecast) {
                $model = $forecast;
            }
        }
        $model->opportunity_id              = $data['opportunity_id'];
        $model->year                        = $data['year'];
        $model->q                           = $data['q'];
        $model->forecast_money              = $data['forecast_money'];
        $model->forecast_money_remain       = $data['forecast_money_remain'];
        $model->order_money                 = $data['order_money'];
        $model->order_rate                  = $data['order_rate'];
        $model->video_forecast_money        = $data['video_forecast_money'];
        $model->video_order_money           = $data['video_order_money'];
        $model->video_order_rate            = $data['video_order_rate'];
        $model->video_forecast_money_remain = $data['video_forecast_money_remain'];
        $model->news_forecast_money         = $data['news_forecast_money'];
        $model->news_order_rate             = $data['news_order_rate'];
        $model->news_order_money            = $data['news_order_money'];
        $model->news_forecast_money_remain  = $data['news_forecast_money_remain'];
        $model->begin                       = $data['begin'];
        $model->end                         = $data['end'];
        $model->save();
        return $model;
    }

    protected function hydrateForUpdate(array $data, Model $model)
    {
        /* @var Forecast $model */
        if (isset($data['year'])) {
            $model->year = $data['year'];
        }
        if (isset($data['q'])) {
            $model->q = $data['q'];
        }
        if (isset($data['forecast_money'])) {
            $model->forecast_money = $data['forecast_money'];
        }
        if (isset($data['forecast_money_remain'])) {
            $model->forecast_money_remain = $data['forecast_money_remain'];
        }
        if (isset($data['order_money'])) {
            $model->order_money = $data['order_money'];
        }
        if (isset($data['order_rate'])) {
            $model->order_rate = $data['order_rate'];
        }
        if (isset($data['video_forecast_money'])) {
            $model->video_forecast_money = $data['video_forecast_money'];
        }
        if (isset($data['video_order_money'])) {
            $model->video_order_money = $data['video_order_money'];
        }
        if (isset($data['video_order_rate'])) {
            $model->video_order_rate = $data['video_order_rate'];
        }
        if (isset($data['video_forecast_money_remain'])) {
            $model->video_forecast_money_remain = $data['video_forecast_money_remain'];
        }
        if (isset($data['news_forecast_money'])) {
            $model->news_forecast_money = $data['news_forecast_money'];
        }
        if (isset($data['news_order_rate'])) {
            $model->news_order_rate = $data['news_order_rate'];
        }
        if (isset($data['news_order_money'])) {
            $model->news_order_money = $data['news_order_money'];
        }
        if (isset($data['forecast_money'])) {
            $model->forecast_money = $data['forecast_money'];
        }
        if (isset($data['forecast_money_remain'])) {
            $model->forecast_money_remain = $data['forecast_money_remain'];
        }
        if (isset($data['news_forecast_money_remain'])) {
            $model->news_forecast_money_remain = $data['news_forecast_money_remain'];
        }
        if (isset($data['begin'])) {
            $model->begin = $data['begin'];
        }
        if (isset($data['end'])) {
            $model->end = $data['end'];
        }
        $model->save();
        return $model;
    }
}

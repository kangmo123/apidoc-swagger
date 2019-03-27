<?php

namespace App\Http\Hydrators;

use App\Models\Model;
use App\Models\Forecast;

/**
 * @apiDefine       CreateDetailParams
 * @apiParam {String} opportunity_id        商机 ID
 * @apiParam {String} forecast_id           商机预估 ID
 * @apiParam {Number} year                  预估年份
 * @apiParam {Number} q                     预估季度
 * @apiParam {Number} forecast_money        整体预估金额
 * @apiParam {Number} platform	            投放平台
 * @apiParam {Number} cooperation_type	    合作形式
 * @apiParam {Number} business_project_id	招商项目对应的项目id
 * @apiParam {String} business_project	    招商项目
 * @apiParam {Number} ad_product_id	        广告产品对应的id
 * @apiParam {String} ad_product	        广告产品
 * @apiParam {Number} resource_id	        资源名称、播放形式对应的id
 * @apiParam {String} resource_name	        资源名称
 * @apiParam {String} other_resource	    其他资源明细
 * @apiParam {String} play_type	            播放形式
 * @apiParam {Number} play_type_id	        播放形式的id
 */
class DetailHydrator extends Hydrator
{
    protected function getCreateRules()
    {
        return [
            'opportunity_id'     => 'nullable|string|max:36',
            'forecast_id'        => 'nullable|string|max:36',
            'year'               => 'required|integer',
            'q'                  => 'required|integer',
            'forecast_money'     => 'required|numeric',
            'platform'           => 'nullable|integer',
            'cooperation_type'   => 'nullable|integer',
            'business_project_id'=> 'nullable|integer',
            'business_project'   => 'nullable|string',
            'ad_product_id'      => 'nullable|integer',
            'ad_product'         => 'nullable|string',
            'resource_id'        => 'nullable|integer',
            'resource_name'      => 'nullable|string',
            'other_resource'     => 'nullable|string',
            'play_type'          => 'nullable|string',
            'play_type_id'       => 'nullable|integer',
        ];
    }

    protected function getUpdateRules()
    {
        /** @var Forecast $forecast */
        $forecast = $this->model;
        return [
            'forecast_detail_id' => 'nullable|string|max:36',
            'year'               => 'required|integer',
            'q'                  => 'required|integer',
            'forecast_money'     => 'required|numeric',
            'platform'           => 'nullable|integer',
            'cooperation_type'   => 'nullable|integer',
            'business_project_id'=> 'nullable|integer',
            'business_project'   => 'nullable|string',
            'ad_product_id'      => 'nullable|integer',
            'ad_product'         => 'nullable|string',
            'resource_id'        => 'nullable|integer',
            'resource_name'      => 'nullable|string',
            'other_resource'     => 'nullable|string',
            'play_type'          => 'nullable|string',
            'play_type_id'       => 'nullable|integer',
        ];
    }

    protected function hydrateForCreate(array $data, Model $model)
    {
        $model->opportunity_id     = $data['opportunity_id'];
        $model->forecast_id        = $data['forecast_id'];
        $model->year               = $data['year'];
        $model->q                  = $data['q'];
        $model->forecast_money     = $data['forecast_money'];
        $model->platform           = $data['platform'];
        $model->cooperation_type   = $data['cooperation_type'];
        $model->business_project_id= $data['business_project_id'];
        $model->business_project   = $data['business_project'];
        $model->ad_product_id      = $data['ad_product_id'];
        $model->ad_product         = $data['ad_product'];
        $model->resource_id        = $data['resource_id'];
        $model->resource_name      = $data['resource_name'];
        $model->other_resource     = $data['other_resource'];
        $model->play_type          = $data['play_type'];
        $model->play_type_id       = $data['play_type_id'];
        $model->save();
        return $model;
    }

    protected function hydrateForUpdate(array $data, Model $model)
    {
        /** @var Detail $model */
        if (isset($data['opportunity_id'])) {
            $model->opportunity_id     = $data['opportunity_id'];
        }
        if (isset($data['forecast_id'])) {
            $model->forecast_id        = $data['forecast_id'];
        }
        if (isset($data['year'])) {
            $model->year               = $data['year'];
        }
        if (isset($data['q'])) {
            $model->q                  = $data['q'];
        }
        if (isset($data['forecast_money'])) {
            $model->forecast_money     = $data['forecast_money'];
        }
        if (isset($data['platform'])) {
            $model->platform           = $data['platform'];
        }
        if (isset($data['cooperation_type'])) {
            $model->cooperation_type   = $data['cooperation_type'];
        }
        if (isset($data['business_project_id'])) {
            $model->business_project_id= $data['business_project_id'];
        }
        if (isset($data['business_project'])) {
            $model->business_project   = $data['business_project'];
        }
        if (isset($data['ad_product_id'])) {
            $model->ad_product_id      = $data['ad_product_id'];
        }
        if (isset($data['ad_product'])) {
            $model->ad_product         = $data['ad_product'];
        }
        if (isset($data['resource_id'])) {
            $model->resource_id        = $data['resource_id'];
        }
        if (isset($data['resource_name'])) {
            $model->resource_name      = $data['resource_name'];
        }
        if (isset($data['other_resource'])) {
            $model->other_resource     = $data['other_resource'];
        }
        if (isset($data['play_type'])) {
            $model->play_type          = $data['play_type'];
        }
        if (isset($data['play_type_id'])) {
            $model->play_type_id       = $data['play_type_id'];
        }
        $model->save();
        return $model;
    }
}

<?php

namespace App\Http\Hydrators;

use App\Models\Model;
use App\Services\ConstDef\OpportunityDef;

/**
 * @apiDefine       CreateProcessParams
 * @apiParam {String} opportunity_id 商机 id，必填
 * @apiParam {Number} step 商机阶段值：0 - 未知，1-初步意向，2-提案/跟进，3-即将锁单，4-锁单，必填
 * @apiParam {String} probability 赢单概率，必填
 * @apiParam {String} comment 阶段说明，选填
 */

/**
 * @apiDefine       ProcessCreateResource
 * @apiParamExample 创建商机阶段参数
 * {
 *      "opportunity_id" : "商机 id", // 商机 id，必填
 *      "step" : "商机阶段", // 商机阶段值，必填
 *      "probability" : "赢单概率", // 赢单概率，必填
 *      "comment" : "阶段说明", // 阶段说明，选填
 * }
 */

class ProcessHydrator extends Hydrator
{
    protected function getCreateRules()
    {
        return [
            'opportunity_id'      => 'required|string|max:36',
            'step'                => 'required|integer|in:' . implode(',', array_keys(OpportunityDef::STEP_MAPS)),
            'probability'         => 'required|string|max:36',
            'comment'             => 'nullable|string|max:255',
        ];
    }

    protected function getUpdateRules()
    {
    }

    protected function hydrateForCreate(array $data, Model $model)
    {
        $model->opportunity_id = $data['opportunity_id'];
        $model->step           = $data['step'];
        $model->probability    = $data['probability'];
        $model->comment        = $data['comment'];

        $model->save();
        return $model;
    }

    protected function hydrateForUpdate(array $data, Model $model)
    {
    }
}

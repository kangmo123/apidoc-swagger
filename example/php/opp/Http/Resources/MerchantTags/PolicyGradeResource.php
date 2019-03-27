<?php

namespace App\Http\Resources\MerchantTags;

use App\Http\Resources\Resource;
use App\Models\MerchantTags\PolicyGrade;

/**
 * @apiDefine PolicyGradeCollectionResource
 * @apiSuccessExample 返回的政策等级集合资源
 * HTTP/1.1 200 OK
 * {
 *     "data": [
 *         {
 *             "id": 1,
 *             "policy_grade": "S",
 *             "begin_date": "2019-01-01",
 *             "end_date": "2019-03-31"
 *         },
 *         {
 *             "id": 2,
 *             "policy_grade": "A",
 *             "begin_date": "2019-04-01",
 *             "end_date": "2019-05-01"
 *         }
 *     ],
 *     "code": 0,
 *     "msg": "OK"
 * }
 */
class PolicyGradeResource extends Resource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var PolicyGrade $model */
        $model = $this;
        return [
            'id' => $model->id,
            'policy_grade' => $model->policy_grade,
            'begin_date' => $model->begin_date->toDateString(),
            'end_date' => $model->end_date->toDateString(),
        ];
    }
}

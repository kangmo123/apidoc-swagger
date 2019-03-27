<?php

namespace App\Http\Resources\MerchantTags;

use App\Http\Resources\Resource;
use App\Models\MerchantTags\MerchantTag;

/**
 * @apiDefine MerchantTagCollectionResource
 * @apiSuccessExample 返回的招商标签集合资源
 * HTTP/1.1 200 OK
 * {
 *     "data": [
 *         {
 *             "id": 2529,                                     //招商标签ID
 *             "tag": "体育-独立招商-事件招商-体育大事件-2018冬奥", //招商标签名称
 *             "merchant_code": [2017111000011, 2017102300016], //招商项目编码
 *             "policy_grades": [
 *                 {
 *                     "id": 1,
 *                     "policy_grade": "S",
 *                     "begin_date": "2019-01-01",
 *                     "end_date": "2019-03-31"
 *                 },
 *                 {
 *                     "id": 2,
 *                     "policy_grade": "A",
 *                     "begin_date": "2019-04-01",
 *                     "end_date": "2019-05-01"
 *                 }
 *             ]
 *         },
 *         {
 *             "id": 3940,
 *             "tag": "视频-独立招商-视频自制-纪录片-风味人间系列冬",
 *             "merchant_code": [2017111000011, 2017102300016],
 *             "policy_grades": []
 *         }
 *     ],
 *     "code": 0,
 *     "msg": "OK",
 *     "page_info": {
 *         "page": 1,
 *         "per_page": 10,
 *         "total_page": 1,
 *         "total_number": 2
 *     }
 * }
 */
class MerchantTagResource extends Resource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        //TODO 这里增加include带上merchant_projects
        /** @var MerchantTag $model */
        $model = $this;
        return [
            'id' => $model->id,
            'tag' => $model->tag,
            'merchant_code' => $model->getMerchantCodes(),
            'policy_grades' => PolicyGradeResource::collection($this->whenLoaded('policyGrades'), true),
        ];
    }
}

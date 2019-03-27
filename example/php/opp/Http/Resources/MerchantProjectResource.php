<?php

namespace App\Http\Resources;

use App\Repositories\MerchantProjects\MerchantProject;

/**
 * @apiDefine MerchantProjectCollectionResource
 * @apiSuccessExample 返回的招商项目集合资源
 * HTTP/1.1 200 OK
 * {
 *     "code": 0,
 *     "msg": "OK",
 *     "data": [
 *         {
 *             "project_code": "2018032300016",
 *             "project_name": "演员的诞生"
 *         },
 *         {
 *             "project_code": "2018042000007",
 *             "project_name": "如懿传"
 *         }
 *     ],
 *     "page_info": {
 *         "page": 1,
 *         "per_page": 10,
 *         "total_page": 1,
 *         "total_number": 2
 *     }
 * }
 */
class MerchantProjectResource extends Resource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var MerchantProject $model */
        $model = $this;
        return [
            'project_code' => $model->project_code,
            'project_name' => $model->project_name,
        ];
    }
}
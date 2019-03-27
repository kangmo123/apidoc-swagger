<?php

namespace App\Http\Resources;

use App\Models\Process;
use Illuminate\Http\Request;

/**
 * @apiDefine ProcessItemResource
 * @apiSuccessExample 返回的商机阶段资源
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 *  "data":
 *      {
 *           "id":"1",
 *           "step":"1", // 商机阶段 1-初步意向, 2-提案/跟进, 3-即将锁单, 4-锁单
 *           "probability":"10", // 进程对应赢单概率
 *           "manager_probability":"0", // 进程对应主管确认赢单概率
 *           "comment":"商机阶段说明",
 *           "created_by": "yoyosjzhang",
 *           "created_at": "2018-11-28 15:40:54",
 *           "updated_by": "yanmeiliu",
 *           "updated_at": "2018-11-29 15:36:08"
 *      }
 * }
 */

/**
 * @apiDefine ProcessCollectionResource
 * @apiSuccessExample 返回的商机阶段集合资源
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 *  "data": [
 *      {
 *           "id":"1",
 *           "step":"1", // 商机阶段 1-初步意向, 2-提案/跟进, 3-即将锁单, 4-锁单
 *           "probability":"10", // 进程对应赢单概率
 *           "manager_probability":"0", // 进程对应主管确认赢单概率
 *           "comment":"商机阶段说明",
 *           "created_by": "yoyosjzhang",
 *           "created_at": "2018-11-28 15:40:54",
 *           "updated_by": "yanmeiliu",
 *           "updated_at": "2018-11-29 15:36:08"
 *      }
 *  ],
 *  "page_info": {
 *      "page": 1,
 *      "per_page": 20,
 *      "total_page": 1,
 *      "total_number": 2
 *  }
 * }
 */
class ProcessResource extends Resource
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
         * @var Process $process
         */
        $process = $this;
        return [
            'id'                            => $process->id,
            'step'                          => $process->step,
            'probability'                   => $process->probability,
            'manager_probability'           => $process->manager_probability,
            'comment'                       => $process->comment,
            'created_by'                    => $process->created_by,
            // 'created_at'                    => $process->created_at->toDateTimeString(),
            'updated_by'                    => $process->updated_by,
            // 'updated_at'                    => $process->updated_at->toDateTimeString(),
        ];
    }
}

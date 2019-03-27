<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\Option;

/**
 * @apiDefine OptionTypeResource
 * @apiSuccessExample 返回指定类型的配置项
 * HTTP/1.1 200 OK
 * {
 * "code": 0,
 * "msg": "OK",
 * "data": [
 *      {
 *          "children": [],
 *          "value": "103",
 *          "text": "外BG",
 *          "label": "外BG",
 *          "cooperation": []
 *      },
 *       {
 *      "value": "101",
 *      "text": "腾讯视频",
 *      "label": "腾讯视频",
 *      "cooperation": {
 *          "1": {
 *              "value": "1",
 *              "text": "招商"
 *              },
 *      }
 *  ],
 * }
 */

/**
 * @apiDefine OptionCollectionResource
 * @apiSuccessExample 返回商机所有配置项层级关系
 * HTTP/1.1 200 OK
 * {
 *  "code": 0,
 *  "msg": "OK",
 * "data": [
 *      {
 *          "children": [],
 *          "value": "103",
 *          "text": "外BG",
 *          "label": "外BG",
 *          "cooperation": []
 *      },
 *       {
 *      "value": "101",
 *      "text": "腾讯视频",
 *      "label": "腾讯视频",
 *      "cooperation": {
 *          "1": {
 *              "value": "1",
 *              "text": "招商"
 *              },
 *      }
 *  ],
 * }
 */
class OptionResource extends Resource
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
         * @var Option $option
         */
        $option = $this;
        return [
            'value'                 => $option->key,
            'text'                  => $option->value,
        ];
    }
}

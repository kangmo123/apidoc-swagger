<?php

namespace App\Exceptions\API;

/**
 * Class BadRequest
 * @package App\Exceptions\API
 * 客户请求出错
 * 如果实在不好放到别的异常里面的时候可以扔这个
 */
/**
 * @apiDefine BadRequest
 * @apiErrorExample 用户请求有误
 * HTTP/1.1 400 Bad Request
 * {
 *   "code": 4400
 *   "msg": "用户请求有误"
 * }
 */
class BadRequest extends APIException
{
    const STATUS_CODE = 400;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4400;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '用户请求有误',
    ];
}

<?php

namespace App\Exceptions\API;

/**
 * Class NotFound
 * @package App\Exceptions\API
 * 请求资源未找到
 * 用户请求某资源但是没有找到
 */
/**
 * @apiDefine NotFound
 * @apiErrorExample 资源未找到
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 4404
 *   "msg": "请求资源未找到"
 * }
 */
class NotFound extends APIException
{
    const STATUS_CODE = 404;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4404;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '请求资源未找到',
    ];
}

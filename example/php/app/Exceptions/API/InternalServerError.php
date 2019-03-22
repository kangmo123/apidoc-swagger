<?php

namespace App\Exceptions\API;

/**
 * Class InternalServerError
 * @package App\Exceptions\API
 * 服务内部错误
 * 接口内又去调用其他服务，但是出现失败无法恢复
 */
/**
 * @apiDefine InternalServerError
 * @apiErrorExample 服务内部错误
 * HTTP/1.1 500 Internal Server Error
 * {
 *   "code": 5500
 *   "msg": "服务内部错误"
 * }
 */
class InternalServerError extends APIException
{
    const STATUS_CODE = 500;

    /**
     * 更多业务Code请从5600开始定义
     */
    const BIZ_CODE_DEFAULT = 5500;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '服务内部错误',
    ];
}

<?php

namespace App\Exceptions\API;

/**
 * Class Forbidden
 * @package App\Exceptions\API
 * 用户无权操作
 * 用户身份已经验证，但是查看权限后发现无权操作
 */
/**
 * @apiDefine Forbidden
 * @apiErrorExample 无权进行该操作
 * HTTP/1.1 403 Forbidden
 * {
 *   "code": 4403
 *   "msg": "无权进行该操作"
 * }
 */
class Forbidden extends APIException
{
    const STATUS_CODE = 403;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4403;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '无权进行该操作',
    ];
}

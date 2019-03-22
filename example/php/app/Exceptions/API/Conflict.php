<?php

namespace App\Exceptions\API;

/**
 * Class Conflict
 * @package App\Exceptions\API
 * 乐观锁冲突
 * 更新同一个数据时因为乐观锁问题发生冲突
 */
/**
 * @apiDefine Conflict
 * @apiErrorExample 发生冲突
 * HTTP/1.1 409 Conflict
 * {
 *   "code": 4409
 *   "msg": "发生冲突"
 * }
 */
class Conflict extends APIException
{
    const STATUS_CODE = 409;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4409;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '发生冲突',
    ];
}

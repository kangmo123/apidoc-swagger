<?php

namespace App\Exceptions\API;

/**
 * Class Unauthorized
 * @package App\Exceptions\API
 * 无法验证用户身份
 * 可能是用户请求时没有传递身份信息
 */

/**
 * @apiDefine Unauthorized
 * @apiErrorExample 无法验证用户身份
 * HTTP/1.1 401 Unauthorized
 * {
 *   "code": 4401
 *   "msg": "无法验证用户身份"
 * }
 */
class Unauthorized extends APIException
{
    const STATUS_CODE = 401;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4401;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '无法验证用户身份',
    ];

    /**
     * Unauthorized constructor.
     * @param int $code
     * @param null $message
     * @param null $challenge 可以使用什么样的验证方式进行验证
     * @param \Exception|null $previous
     */
    public function __construct($message = null, $code = 0, $challenge = null, \Exception $previous = null)
    {
        $headers = [];
        if ($challenge) {
            $headers['WWW-Authenticate'] = $challenge;
        }
        parent::__construct($message, $code, $previous, $headers);
    }
}

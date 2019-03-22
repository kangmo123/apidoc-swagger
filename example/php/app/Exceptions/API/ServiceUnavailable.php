<?php

namespace App\Exceptions\API;

/**
 * Class ServiceUnavailable
 * @package App\Exceptions\API
 * 服务暂不可用
 * 出现短暂可恢复错误时返回此错误
 */
/**
 * @apiDefine ServiceUnavailable
 * @apiErrorExample 服务暂不可用
 * HTTP/1.1 503 Service Unavailable
 * {
 *   "code": 5503
 *   "msg": "服务暂不可用"
 * }
 */
class ServiceUnavailable extends APIException
{
    const STATUS_CODE = 503;

    /**
     * 更多业务Code请从5600开始定义
     */
    const BIZ_CODE_DEFAULT = 5503;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '服务暂不可用',
    ];

    /**
     * ServiceUnavailable constructor.
     * @param int $code
     * @param null $message
     * @param null $retryAfter 可以在多少秒之后进行重试
     * @param \Exception|null $previous
     */
    public function __construct($code = 0, $message = null, $retryAfter = null, \Exception $previous = null)
    {
        $headers = [];
        if ($retryAfter) {
            $headers['Retry-After'] = $retryAfter;
        }
        parent::__construct($code, $message, $previous, $headers);
    }
}

<?php

namespace App\Exceptions\API;

/**
 * Class MethodNotAllowed
 * @package App\Exceptions\API
 * 请求方法不支持
 * 资源只支持GET,POST，用DELETE去请求时会返回
 * 因为这个方法我还不支持
 */
/**
 * @apiDefine MethodNotAllowed
 * @apiErrorExample 请求方法不支持
 * HTTP/1.1 405 Method Not Allowed
 * {
 *   "code": 4405
 *   "msg": "请求方法不支持"
 * }
 */
class MethodNotAllowed extends APIException
{
    const STATUS_CODE = 405;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4405;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '请求方法不支持',
    ];

    /**
     * MethodNotAllowed constructor.
     * @param int $code
     * @param null $message
     * @param array $allow 实际允许哪些操作，比如GET、POST、PUT、DELETE
     * @param \Exception|null $previous
     */
    public function __construct($code = 0, $message = null, array $allow = [], \Exception $previous = null)
    {
        $headers = [];
        if ($allow) {
            $headers['Allow'] = strtoupper(implode(', ', $allow));
        }
        parent::__construct($code, $message, $previous, $headers);
    }
}

<?php

namespace App\Exceptions\Business;
use App\Exceptions\API\APIException;

/**
 * 通用业务异常类
 * Class ParamException
 * @package App\Exceptions\API
 */
class BusinessException extends APIException
{
    const STATUS_CODE = 200;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 2000;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '业务逻辑错误',
    ];

    public function __construct($message = null, $code = self::BIZ_CODE_DEFAULT)
    {
        parent::__construct($code, $message);
    }
}

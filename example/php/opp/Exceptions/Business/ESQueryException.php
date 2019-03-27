<?php

namespace App\Exceptions\Business;

use App\Exceptions\API\APIException;

/**
 * ES查询异常类
 * Class ParamException
 * @package App\Exceptions\API
 */
class ESQueryException extends APIException
{
    const STATUS_CODE = 200;
    const BIZ_CODE_DEFAULT = 6000;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '参数错误',
    ];

    public function __construct($message = null, $code = self::BIZ_CODE_DEFAULT)
    {
        parent::__construct($code, $message);
    }
}

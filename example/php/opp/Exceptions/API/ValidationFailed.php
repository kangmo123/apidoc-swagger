<?php

namespace App\Exceptions\API;

use Illuminate\Support\MessageBag;
use App\Exceptions\API\Contract\MessageBagErrors;

/**
 * Class ValidationFailed
 * @package App\Exceptions\API
 * 数据验证失败
 * 在POST、PUT时候请求的JSON未通过Validation验证
 */
/**
 * @apiDefine ValidationFailed
 * @apiErrorExample 数据未通过验证
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 4422
 *   "msg": "数据未通过验证"
 *   "data": [
 *     "title": [
 *       "最大长度为40个字符",
 *       "其中不能包含特殊符号"
 *     ],
 *     "content": [
 *       "最大长度为400个字符"
 *     ]
 *   ]
 * }
 */
class ValidationFailed extends APIException implements MessageBagErrors
{
    const STATUS_CODE = 422;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4422;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '数据未通过验证',
    ];

    /**
     * MessageBag errors.
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * ValidationFailed constructor.
     * @param int $code
     * @param null $message
     * @param null $errors
     * @param \Exception|null $previous
     */
    public function __construct($code = 0, $message = null, $errors = null, \Exception $previous = null)
    {
        if (is_null($errors)) {
            $this->errors = new MessageBag;
        } elseif (is_array($errors)) {
            $this->errors = new MessageBag($errors);
        } elseif ($errors instanceof MessageBag) {
            $this->errors = $errors;
        }
        parent::__construct($code, $message, $previous);
    }

    /**
     * Get the errors message bag.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Determine if message bag has any errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return $this->errors ? !$this->errors->isEmpty() : false;
    }
}

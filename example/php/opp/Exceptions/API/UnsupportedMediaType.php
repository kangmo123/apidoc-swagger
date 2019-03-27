<?php

namespace App\Exceptions\API;

/**
 * Class UnsupportedMediaType
 * @package App\Exceptions\API
 * 请求的媒体格式不支持
 * 例如只支持JSON，用户请求YAMl
 */
/**
 * @apiDefine UnsupportedMediaType
 * @apiErrorExample 请求的媒体格式不支持
 * HTTP/1.1 415 Unsupported MediaType
 * {
 *   "code": 4415
 *   "msg": "请求的媒体格式不支持"
 * }
 */
class UnsupportedMediaType extends APIException
{
    const STATUS_CODE = 415;

    /**
     * 更多业务Code请从4500开始定义
     */
    const BIZ_CODE_DEFAULT = 4415;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '请求的媒体格式不支持',
    ];
}

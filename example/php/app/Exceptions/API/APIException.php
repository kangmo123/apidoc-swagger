<?php

namespace App\Exceptions\API;

use App\Exceptions\API\Contract\APIExceptionInterface;
use App\Exceptions\API\Contract\MessageBagErrors;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class APIException extends HttpException implements APIExceptionInterface
{
    const STATUS_CODE = 500;

    const BIZ_CODE_DEFAULT = 5500;

    const MESSAGE_MAP = [
        self::BIZ_CODE_DEFAULT => '服务内部错误',
    ];

    public function __construct($message = null, $code = 0, \Exception $previous = null, array $headers = [])
    {
        $code = (!empty($code) && isset(static::MESSAGE_MAP[$code])) ? $code : static::BIZ_CODE_DEFAULT;
        $message = !empty($message) ? $message : static::MESSAGE_MAP[$code];
        parent::__construct(static::STATUS_CODE, $message, $previous, $headers, $code);
    }

    public function render($request)
    {
        $data = [
            'code' => $this->getCode(),
            'msg' => $this->getMessage(),
        ];
        if ($this instanceof MessageBagErrors and $this->hasErrors()) {
            $data['data'] = $this->getErrors();
        } elseif ($this instanceof InternalServerError and env('APP_DEBUG')) {
            $data['data'] = [
                'class' => get_class($this->getPrevious()),
                'message' => $this->getPrevious()->getMessage(),
                'code' => $this->getPrevious()->getCode(),
                'file' => $this->getPrevious()->getFile(),
                'line' => $this->getPrevious()->getLine(),
                'trace' => explode("\n", $this->getPrevious()->getTraceAsString()),
            ];
        }
        $response = response()->json($data)->setStatusCode($this->getStatusCode());
        $headers = $this->getHeaders();
        foreach ($headers as $key => $val) {
            $response->header($key, $val, true);
        }
        return $response;
    }
}

<?php

namespace App\Exceptions;

use App\Exceptions\API\Unauthorized;
use App\Exceptions\Business\BusinessException;
use App\Exceptions\Business\ESDocumentException;
use App\Exceptions\Business\ESQueryException;
use App\Exceptions\Business\ParamException;
use Exception;
use App\Exceptions\API\NotFound;
use App\Exceptions\API\Forbidden;
use App\Exceptions\API\ValidationFailed;
use App\Exceptions\API\MethodNotAllowed;
use App\Exceptions\API\InternalServerError;
use App\Exceptions\API\Contract\APIExceptionInterface;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        ParamException::class,
        BusinessException::class,
        ESDocumentException::class,
        ESQueryException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $e
     * @return void
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if (env('SENTRY_DSN') && app()->bound('sentry') && $this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof ModelNotFoundException) {
            $e = new NotFound(0, '请求资源未找到', $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new Forbidden(0, null, $e);
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $e = new MethodNotAllowed(0, null, [], $e);
        } elseif ($e instanceof NotFoundHttpException) {
            $e = new NotFound(0, '请求路径未找到', $e);
        } elseif ($e instanceof ValidationException) {
            $e = new ValidationFailed(0, null, $e->validator->getMessageBag(), $e);
        } elseif ($e instanceof UnauthorizedException) {
            $e = new Unauthorized(0, '您没有操作资源的权限', [], $e);
        } elseif (!$e instanceof APIExceptionInterface) {
            $e = new InternalServerError(0, null, $e);
        }
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return $response;
        }
        return parent::render($request, $e);
    }
}

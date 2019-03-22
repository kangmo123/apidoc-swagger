<?php

namespace App\Exceptions;

use App\Exceptions\API\Contract\APIExceptionInterface;
use App\Exceptions\API\Forbidden;
use App\Exceptions\API\InternalServerError;
use App\Exceptions\API\MethodNotAllowed;
use App\Exceptions\API\NotFound;
use App\Exceptions\API\ValidationFailed;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            /** @var ErrorReporter $reporter */
            $reporter = app(ErrorReporter::class);
            $reporter->report($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof ModelNotFoundException) {
            $e = new NotFound('请求资源未找到', 0, $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new Forbidden(null, 0, $e);
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $e = new MethodNotAllowed(null, 0, [], $e);
        } elseif ($e instanceof NotFoundHttpException) {
            $e = new NotFound('请求路径未找到', 0, $e);
        } elseif ($e instanceof ValidationException) {
            $e = new ValidationFailed(null, 0, $e->validator->getMessageBag(), $e);
        } elseif (!$e instanceof APIExceptionInterface) {
            $e = new InternalServerError(null, 0, $e);
        }
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return $response;
        }
        return parent::render($request, $e);
    }
}

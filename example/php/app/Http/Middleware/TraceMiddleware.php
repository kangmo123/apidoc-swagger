<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class TraceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);
        if ($request->hasHeader('x-request-id')) {
            if (method_exists($response, 'header')) {
                $response->header('x-request-id', $request->header('x-request-id'));
            }
        }
        if ($request->hasHeader('x-b3-traceid')) {
            if (method_exists($response, 'header')) {
                $response->header('x-trace-id', $request->header('x-b3-traceid'));
            }
        }

        return $response;
    }
}

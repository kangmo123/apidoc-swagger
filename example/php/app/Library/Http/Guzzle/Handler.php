<?php

namespace App\Library\Http\Guzzle;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;

class Handler
{
    public static function getMicroServiceHandler()
    {
        $stack = HandlerStack::create();

        //Add Request Middleware
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return static::forwardUserRequestHeader($request);
        }), 'forwardUserRequestHeader');
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return static::forwardEnvoyRequestHeader($request);
        }), 'forwardEnvoyRequestHeader');
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return static::forwardLightStepRequestHeader($request);
        }), 'forwardLightStepRequestHeader');
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return static::forwardZipkinRequestHeader($request);
        }), 'forwardZipkinRequestHeader');

        return $stack;
    }

    protected static function forwardUserRequestHeader(RequestInterface $request)
    {
        /** @var Request $req */
        $req = app('request');
        if ($req && $req->hasHeader('x-staff-name')) {
            $request = $request->withHeader('x-staff-name', $req->header('x-staff-name'));
        }
        if ($req && $req->hasHeader('x-mock-staff-name')) {
            $request = $request->withHeader('x-mock-staff-name', $req->header('x-mock-staff-name'));
        }
        return $request;
    }

    protected static function forwardEnvoyRequestHeader(RequestInterface $request)
    {
        /** @var Request $req */
        $req = app('request');
        if ($req && $req->hasHeader('x-request-id')) {
            $request = $request->withHeader('x-request-id', $req->header('x-request-id'));
        }
        return $request;
    }

    protected static function forwardLightStepRequestHeader(RequestInterface $request)
    {
        /** @var Request $req */
        $req = app('request');
        if ($req && $req->hasHeader('x-ot-span-context')) {
            $request = $request->withHeader('x-ot-span-context', $req->header('x-ot-span-context'));
        }
        return $request;
    }

    protected static function forwardZipkinRequestHeader(RequestInterface $request)
    {
        /** @var Request $req */
        $req = app('request');
        if ($req && $req->hasHeader('x-b3-traceid')) {
            $request = $request->withHeader('x-b3-traceid', $req->header('x-b3-traceid'));
        }
        if ($req && $req->hasHeader('x-b3-spanid')) {
            $request = $request->withHeader('x-b3-spanid', $req->header('x-b3-spanid'));
        }
        if ($req && $req->hasHeader('x-b3-parentspanid')) {
            $request = $request->withHeader('x-b3-parentspanid', $req->header('x-b3-parentspanid'));
        }
        if ($req && $req->hasHeader('x-b3-sampled')) {
            $request = $request->withHeader('x-b3-sampled', $req->header('x-b3-sampled'));
        }
        if ($req && $req->hasHeader('x-b3-flags')) {
            $request = $request->withHeader('x-b3-flags', $req->header('x-b3-flags'));
        }
        return $request;
    }
}

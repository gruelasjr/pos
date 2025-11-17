<?php

namespace Equidna\Toolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestContext
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->headers->get('X-Request-Id', Str::uuid()->toString());
        app()->instance('request-id', $requestId);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}

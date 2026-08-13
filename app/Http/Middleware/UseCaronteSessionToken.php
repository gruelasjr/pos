<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bridges the same-origin OIDC browser session into SDK API authentication. */
class UseCaronteSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null && $request->hasSession()) {
            $token = $request->session()->get((string) config('caronte.session_key'));

            if (is_string($token) && $token !== '') {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}

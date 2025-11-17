<?php

namespace Equdna\SwiftAuth\Http\Middleware;

use Closure;
use Equdna\SwiftAuth\Models\SwiftToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbility
{
    public function __construct(private string $ability = '*')
    {
    }

    public function handle(Request $request, Closure $next, string $ability = '*'): Response
    {
        /** @var SwiftToken|null $token */
        $token = $request->attributes->get('swift_token');
        if (!$token) {
            abort(403, 'Token missing');
        }

        if (!in_array('*', $token->abilities ?? [], true) && !in_array($ability, $token->abilities ?? [], true)) {
            abort(403, 'Insufficient token ability');
        }

        return $next($request);
    }
}

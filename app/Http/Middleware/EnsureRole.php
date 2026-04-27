<?php

/**
 * Middleware to enforce role-based access for API routes.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Middleware
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return new JsonResponse(['message' => 'No autenticado'], 401);
        }

        if (empty($roles) || $user->hasRoles($roles)) {
            return $next($request);
        }

        return new JsonResponse(['message' => 'No autorizado'], 403);
    }
}

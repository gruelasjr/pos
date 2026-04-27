<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(private UserTokenService $tokenService)
    {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return new JsonResponse(['message' => 'Token requerido.'], 401);
        }

        $userToken = $this->tokenService->validateToken(trim(substr($header, 7)));

        if (! $userToken) {
            return new JsonResponse(['message' => 'Token invalido o expirado.'], 401);
        }

        $user = User::query()->with('roles')->find($userToken->id_user);

        if (! $user || ! $user->active) {
            return new JsonResponse(['message' => 'Usuario no autorizado.'], 401);
        }

        $request->setUserResolver(fn () => $user);
        $request->attributes->set('user_token', $userToken);
        $userToken->markAsUsed();

        return $next($request);
    }
}

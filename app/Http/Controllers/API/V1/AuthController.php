<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(Request $request, UserTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->error('Credenciales invalidas', [], 401);
        }

        if (! $user->active) {
            return $this->error('El usuario esta inactivo', [], 403);
        }

        $expiresAt = now()->addMinutes((int) config('security.api_token_ttl_minutes', 480));
        $issued = $tokens->createToken($user, 'pos-api', ['*'], $expiresAt);

        return $this->success('Inicio de sesion exitoso', [
            'token' => $issued['token'],
            'expires_at' => $expiresAt->toISOString(),
            'user' => $user->load('roles'),
        ]);
    }
}

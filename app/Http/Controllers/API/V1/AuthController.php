<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Equidna\SwifthAuth\Facades\SwiftAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('Credenciales inválidas', [], 401);
        }

        if (!$user->active) {
            return $this->error('El usuario está inactivo', [], 403);
        }

        SwiftAuth::login($user);

        $token = $user->createToken('api')->plainTextToken;

        return $this->success('Inicio de sesión exitoso', [
            'token' => $token,
            'user' => $user->load('roles'),
        ]);
    }
}

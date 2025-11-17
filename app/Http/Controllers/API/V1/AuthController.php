<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Equdna\SwiftAuth\Services\SwiftAuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(Request $request, SwiftAuthManager $swift): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('auth_failed', 'Credenciales inválidas', 401);
        }

        if (!$user->active) {
            return $this->error('user_inactivo', 'El usuario está inactivo', 403);
        }

        $token = $swift->issueToken($user->id, 'api');

        return $this->success([
            'token' => $token['token'],
            'user' => $user,
        ]);
    }
}

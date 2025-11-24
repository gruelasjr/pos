<?php

/**
 * Controller: Authentication endpoints (API v1).
 *
 * Handles login, logout and token issuance for the POS API.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * Authentication controller for API v1.
 *
 * Handles login and token issuance.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controller responsible for authentication endpoints.
 */
/**
 * Authentication controller.
 *
 * Provides login and token management endpoints for API clients.
 *
 * @package   App\Http\Controllers\API\V1
 */
class AuthController extends BaseApiController
{
    /**
     * Login a user and return an API token.
     *
     * @param  Request $request
     * @return JsonResponse
     */
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

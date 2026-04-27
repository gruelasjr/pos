<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_a_swift_auth_api_token(): void
    {
        User::factory()->seller()->create([
            'email' => 'seller@example.test',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'seller@example.test',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'expires_at', 'user'],
            ]);

        $this->assertDatabaseCount('swift_auth_UserTokens', 1);
    }

    public function test_inactive_users_cannot_login(): void
    {
        User::factory()->seller()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('secret-password'),
            'active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'secret-password',
        ])->assertForbidden();
    }
}

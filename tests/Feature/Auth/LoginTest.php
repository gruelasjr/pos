<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_and_receive_token(): void
    {
        User::factory()->seller()->create([
            'email' => 'agent@pos.test',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@pos.test',
            'password' => 'secret123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.user.email', 'agent@pos.test')
            ->assertJsonStructure(['data' => ['token']]);
    }
}

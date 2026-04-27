<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsPosFixtures;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use BuildsPosFixtures;
    use RefreshDatabase;

    public function test_protected_api_requires_a_bearer_token(): void
    {
        $this->getJson('/api/v1/carts')->assertUnauthorized();
    }

    public function test_role_middleware_blocks_auditors_from_pos_carts(): void
    {
        $auditor = User::factory()->auditor()->create();

        $this
            ->withToken($this->bearerTokenFor($auditor))
            ->getJson('/api/v1/carts')
            ->assertForbidden();
    }
}

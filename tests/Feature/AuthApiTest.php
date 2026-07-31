<?php

namespace Tests\Feature;

use App\Http\Middleware\SyncCaronteUser;
use App\Models\Role;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_password_login_has_been_removed(): void
    {
        $route = collect(app('router')->getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/auth/login' && in_array('POST', $route->methods(), true)
        );

        $this->assertTrue($route === null || str_starts_with((string) $route->getActionName(), 'Ometra\\Caronte\\'));
    }

    public function test_caronte_identity_is_projected_to_a_tenant_scoped_shadow_user(): void
    {
        $context = new TenantContext();
        $context->set('tenant-a');
        app()->instance(TenantContext::class, $context);
        $request = Request::create('/api/v1/carts', 'GET');
        $request->attributes->set('caronte.user', (object) [
            'id_tenant' => 'tenant-a',
            'uri_user' => 'usr-caronte-1',
            'name' => 'Ada Seller',
            'email' => 'ada@example.test',
            'roles' => [(object) ['name' => Role::SELLER]],
        ]);

        (new SyncCaronteUser())->handle($request, function (Request $request) {
            $this->assertTrue($request->user()->isSeller());
            $this->assertSame('tenant-a', $request->user()->tenant_id);
            return response()->noContent();
        });

        $this->assertDatabaseHas('pos_users', [
            'tenant_id' => 'tenant-a',
            'uri_user' => 'usr-caronte-1',
        ]);
    }

    public function test_required_pos_roles_are_declared_for_caronte_sync(): void
    {
        $this->assertSame(Role::ALL, array_keys(config('caronte.roles')));
        $this->assertSame('oidc', config('caronte.auth_mode'));
    }
}

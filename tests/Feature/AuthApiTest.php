<?php

namespace Tests\Feature;

use App\Http\Middleware\SyncCaronteUser;
use App\Http\Middleware\UseCaronteSessionToken;
use App\Models\Role;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Tests\TestCase;
use Ometra\Caronte\Http\Middleware\ValidateUserToken;

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

    public function test_browser_api_routes_start_the_oidc_session(): void
    {
        $route = collect(app('router')->getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/warehouses' && in_array('GET', $route->methods(), true)
        );

        $this->assertNotNull($route);
        $this->assertContains(StartSession::class, $route->gatherMiddleware());
        $this->assertContains(UseCaronteSessionToken::class, $route->gatherMiddleware());
        $this->assertContains('caronte.session', $route->gatherMiddleware());

        $middleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewarePriority();
        $this->assertLessThan(
            array_search(ValidateUserToken::class, $middleware, true),
            array_search(UseCaronteSessionToken::class, $middleware, true),
        );
    }

    public function test_browser_session_token_is_bridged_to_api_bearer_authentication(): void
    {
        $request = Request::create('/api/v1/warehouses', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put(config('caronte.session_key'), 'oidc-id-token');

        (new UseCaronteSessionToken())->handle($request, function (Request $request) {
            $this->assertSame('oidc-id-token', $request->bearerToken());

            return response()->noContent();
        });
    }

    public function test_browser_session_never_overrides_an_explicit_bearer_token(): void
    {
        $request = Request::create('/api/v1/warehouses', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer explicit-token',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put(config('caronte.session_key'), 'session-token');

        (new UseCaronteSessionToken())->handle($request, function (Request $request) {
            $this->assertSame('explicit-token', $request->bearerToken());

            return response()->noContent();
        });
    }
}

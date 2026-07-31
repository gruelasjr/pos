<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Test-server-only Caronte boundary used by browser E2E tests. */
class E2eCaronteAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('testing') && config('app.e2e_enabled'), 403);

        $uriUser = match ($request->bearerToken()) {
            'e2e-demo-admin' => 'demo-admin',
            'e2e-demo-auditor' => 'demo-auditor',
            default => 'demo-seller',
        };

        /** @var User $user */
        $user = User::withoutGlobalScopes()->where('uri_user', $uriUser)->firstOrFail();
        $context = new TenantContext();
        $context->set((string) $user->tenant_id);
        app()->instance(TenantContext::class, $context);
        $request->attributes->set('caronte.user', (object) [
            'id_tenant' => $user->tenant_id,
            'uri_user' => $user->uri_user,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => collect($user->roles)->map(fn (array $role): object => (object) $role)->all(),
        ]);

        return $next($request);
    }
}

<?php

namespace Tests\Support;

use App\Models\User;
use Closure;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class FakeCaronteAuthentication
{
    /** @var array<string, User> */
    private static array $tokens = [];

    public static function tokenFor(User $user): string
    {
        $token = 'test-caronte-' . Str::random(32);
        self::$tokens[$token] = $user;
        return $token;
    }

    public static function reset(): void { self::$tokens = []; }

    public function handle(Request $request, Closure $next): Response
    {
        $user = self::$tokens[(string) $request->bearerToken()] ?? null;
        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $context = new TenantContext();
        $context->set((string) $user->tenant_id);
        app()->instance(TenantContext::class, $context);
        $request->attributes->set('caronte.user', (object) [
            'id_tenant' => $user->tenant_id,
            'uri_user' => $user->uri_user,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => collect($user->roles)->map(fn ($role) => (object) $role)->all(),
        ]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

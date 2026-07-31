<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class SyncCaronteUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $identity = $request->attributes->get('caronte.user');

        abort_unless(is_object($identity) && filled($identity->uri_user ?? null), 401, 'Identidad Caronte inválida.');
        abort_unless(filled($identity->id_tenant ?? null), 403, 'Caronte no proporcionó un tenant.');

        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => (string) $identity->id_tenant, 'uri_user' => (string) $identity->uri_user],
            [
                'name' => (string) ($identity->name ?? ''),
                'email' => (string) ($identity->email ?? ''),
                'roles' => collect($identity->roles ?? [])->map(fn ($role) => (array) $role)->values()->all(),
            ],
        );

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

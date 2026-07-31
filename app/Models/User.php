<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

/**
 * Local, tenant-scoped projection of a Caronte identity.
 *
 * Credentials and access tokens deliberately never live in this table.
 */
class User extends Authenticatable
{
    use BelongsToTenant;
    use HasFactory;
    use Notifiable;

    public $timestamps = false;

    protected $table = 'pos_users';

    protected string $tenantKey = 'tenant_id';

    protected $fillable = ['tenant_id', 'uri_user', 'name', 'email', 'roles'];

    protected $casts = ['roles' => 'array'];

    protected $hidden = ['pivot'];

    public function hasRoles(string|array $roles): bool
    {
        $expected = collect(Arr::wrap($roles))->map(function ($role): string {
            return match (strtolower((string) $role)) {
                'admin' => Role::ADMIN,
                'vendedor', 'seller' => Role::SELLER,
                'auditor' => Role::AUDITOR,
                default => strtolower((string) $role),
            };
        });

        return $expected->intersect($this->roleNames())->isNotEmpty();
    }

    public function roleNames(): \Illuminate\Support\Collection
    {
        return collect($this->roles ?? [])->map(function ($role): string {
            if (is_array($role)) {
                return strtolower((string) ($role['slug'] ?? $role['name'] ?? $role['uri_role'] ?? ''));
            }

            return strtolower((string) (is_object($role)
                ? ($role->slug ?? $role->name ?? $role->uri_role ?? '')
                : $role));
        })->filter()->values();
    }

    public function availableActions(): array
    {
        return $this->roleNames()->flatMap(fn (string $role) => match ($role) {
            'pos-admin' => ['pos.*'],
            'pos-auditor' => ['reports.view', 'sales.view', 'audit.view'],
            'pos-seller' => ['pos.checkout', 'sales.view', 'customers.manage'],
            default => [],
        })->unique()->values()->all();
    }

    public function isAdmin(): bool
    {
        return $this->hasRoles('pos-admin');
    }
    public function isAuditor(): bool
    {
        return $this->hasRoles('pos-auditor');
    }
    public function isSeller(): bool
    {
        return $this->hasRoles('pos-seller');
    }
}

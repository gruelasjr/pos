<?php

/**
 * Application User model.
 *
 * Extends the Swift Auth base user to add application-specific behavior
 * such as role helpers and UUID generation on creation.
 *
 * PHP 8.1+
 *
 * @package App\Models
 */

namespace App\Models;

use Equidna\SwifthAuth\Models\User as SwiftUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class User extends SwiftUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $with = ['roles'];

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (!$user->uuid) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    public function hasRoles(string|array $roles): bool
    {
        $roles = Arr::wrap($roles);

        return $this->roles->contains(fn(Role $role) => in_array($role->slug, $roles, true));
    }

    public function availableActions(): array
    {
        return $this->roles
            ->flatMap(function (Role $role) {
                $actions = $role->actions;

                if (is_array($actions)) {
                    return $actions;
                }

                return array_filter(array_map('trim', explode(',', (string) $actions)));
            })
            ->unique()
            ->values()
            ->all();
    }

    public function isAdmin(): bool
    {
        return $this->hasRoles('admin');
    }

    public function isAuditor(): bool
    {
        return $this->hasRoles('auditor');
    }

    public function isSeller(): bool
    {
        return $this->hasRoles('vendedor');
    }
}

<?php

/**
 * Model: User.
 *
 * Represents an application user with authentication and roles.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

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

use Equidna\SwiftAuth\Models\Role as SwiftRole;
use Equidna\SwiftAuth\Models\User as SwiftUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Represents an authenticated application user.
 *
 * @method object createToken(string $name, array $abilities = ['*'])
 *
 * @package   App\Models
 */
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

    /**
     * @return BelongsToMany<
     *     \Equidna\SwiftAuth\Models\Role,
     *     $this,
     *     \Illuminate\Database\Eloquent\Relations\Pivot,
     *     'pivot'
     * >
     */
    public function roles(): BelongsToMany
    {
        /** @var BelongsToMany<\Equidna\SwiftAuth\Models\Role, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();

        return $relation;
    }

    public function hasRoles(string|array $roles): bool
    {
        $roles = Arr::wrap($roles);

        return $this->roles->contains(fn(SwiftRole $role) => in_array($role->slug, $roles, true));
    }

    public function availableActions(): array
    {
        return $this->roles
            ->flatMap(function (SwiftRole $role) {
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

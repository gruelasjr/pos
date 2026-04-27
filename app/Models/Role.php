<?php

/**
 * Model: Role.
 *
 * Represents user roles and permissions used by access control.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Role model.
 *
 * Extends the SwiftAuth Role model to add application-specific behavior.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Equidna\SwiftAuth\Models\Role as SwiftRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Application Role entity.
 */
/**
 * Represents a user role used for access control.
 *
 * @package   App\Models
 */
class Role extends SwiftRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

    public function getIdAttribute(): ?int
    {
        $key = $this->getKeyName();

        return $this->attributes[$key] ?? null;
    }

    public function getSlugAttribute(?string $value): string
    {
        return $value ?: Str::slug((string) $this->name);
    }

    /**
     * @return BelongsToMany<
     *     \Equidna\SwiftAuth\Models\User,
     *     $this,
     *     \Illuminate\Database\Eloquent\Relations\Pivot,
     *     'pivot'
     * >
     */
    public function users(): BelongsToMany
    {
        /** @var BelongsToMany<\Equidna\SwiftAuth\Models\User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(
            User::class,
            (string) config('swift-auth.table_prefix', 'swift_auth_') . 'UsersRoles',
            'id_role',
            'id_user'
        );

        return $relation;
    }
}

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

    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

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
        $relation = $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')->withTimestamps();

        return $relation;
    }
}

<?php

namespace App\Models;

use Equidna\SwifthAuth\Models\Role as SwiftRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Global lookup table: intentionally has no tenant scope. */
class CustomerRegistrationLink extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'tenant_id', 'sale_id', 'token_hash', 'expires_at', 'used_at'];
    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    protected $hidden = ['token_hash'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id ??= (string) Str::uuid());
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

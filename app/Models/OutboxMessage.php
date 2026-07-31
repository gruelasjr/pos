<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** @property string $tenant_id */
class OutboxMessage extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'status',
        'attempts',
        'last_error',
        'lock_token',
        'locked_at',
        'available_at',
        'processed_at',
        'dead_lettered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
        'locked_at' => 'datetime',
        'dead_lettered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->id ??= (string) Str::uuid();
            $model->available_at ??= now();
        });
    }
}

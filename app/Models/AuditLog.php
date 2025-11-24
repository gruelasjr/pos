<?php

/**
 * Model: AuditLog.
 *
 * Stores audit entries for important domain events.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

/**
 * Audit log model.
 *
 * PHP 8.1+
 *
 * @package   App\Models
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Audit log entry model.
 *
 * Stores audit records for important domain events and actors.
 *
 * @package   App\Models
 */
class AuditLog extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'event',
        'auditable_type',
        'auditable_id',
        'user_id',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->id ??= (string) Str::uuid();
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

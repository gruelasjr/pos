<?php

namespace App\Models;

use Equidna\BeeHive\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IdempotencyKey extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'key',
        'route',
        'method',
        'request_hash',
        'response_body',
        'status_code',
        'user_id',
    ];

    protected $casts = [
        'response_body' => 'array',
        'status_code' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->id ??= (string) Str::uuid();
        });
    }
}

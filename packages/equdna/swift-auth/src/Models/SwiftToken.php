<?php

namespace Equdna\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SwiftToken extends Model
{
    use HasFactory;

    protected $table = 'swift_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function findToken(string $plain): ?self
    {
        $hash = hash('sha256', $plain);

        return static::query()
            ->where('token', $hash)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public static function generatePlainTextToken(): string
    {
        return Str::random(80);
    }
}

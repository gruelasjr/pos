<?php

namespace Equdna\SwiftAuth\Services;

use Equdna\SwiftAuth\Models\SwiftToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SwiftAuthManager
{
    public function __construct()
    {
    }

    public function issueToken(int $userId, string $name, array $abilities = ['*'], ?Carbon $expiresAt = null): array
    {
        $plainToken = Str::random(80);
        $token = new SwiftToken();
        $token->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'abilities' => $abilities,
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);
        $token->save();

        return ['id' => $token->id, 'token' => $plainToken];
    }
}

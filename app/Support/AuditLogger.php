<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private Request $request)
    {
    }

    public function log(string $event, ?Authenticatable $user, ?string $auditableType, ?string $auditableId, array $payload = []): void
    {
        AuditLog::create([
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'user_id' => $user?->getAuthIdentifier(),
            'payload' => $payload,
            'ip_address' => $this->request->ip(),
        ]);
    }
}

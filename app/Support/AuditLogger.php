<?php

/**
 * Utility: application audit logger.
 *
 * Provides centralized audit logging for important domain events.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

/**
 * Audit logger helpers.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use App\Support\SecurityHelpers;

/**
 * Utility: audit logger for domain events.
 *
 * Records audit entries for important operations in the application.
 *
 * @package   App\Support
 */
class AuditLogger
{
    public function __construct(private Request $request)
    {
        // No body
    }

    public function log(
        string $event,
        ?Authenticatable $user,
        ?string $auditableType,
        ?string $auditableId,
        array $payload = []
    ): void {
        $payload = SecurityHelpers::redact($payload);

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

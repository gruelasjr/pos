<?php

/**
 * SMS notifications provider.
 *
 * PHP 8.1+
 *
 * @package   App\Services\Notifications
 */

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use App\Support\SecurityHelpers;

/**
 * Notification SMS provider.
 *
 * Sends SMS notifications via the configured gateway.
 *
 * @package   App\Services\Notifications
 */
class SmsProvider
{
    public function send(string $to, string $message): void
    {
        $payload = SecurityHelpers::redact([
            'to' => $to,
            'message' => $message,
        ]);

        Log::channel('stack')->info('sms_stub', $payload);
    }
}

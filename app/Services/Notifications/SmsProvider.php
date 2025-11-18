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

class SmsProvider
{
    public function send(string $to, string $message): void
    {
        Log::channel('stack')->info('sms_stub', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}

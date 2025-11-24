<?php

/**
 * Mailer notification stub.
 *
 * Provides a simple mail sending implementation used by the application.
 *
 * PHP 8.1+
 *
 * @package   App\Services\Notifications
 */

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use App\Support\SecurityHelpers;

/**
 * Mailer service (stub) that logs outgoing messages.
 */
/**
 * Notification mailer.
 *
 * Sends emails using the configured mail transport for notifications.
 *
 * @package   App\Services\Notifications
 */
class Mailer
{
    /**
     * Send an HTML message to a destination.
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $html
     * @return void
     */
    public function send(string $to, string $subject, string $html): void
    {
        $payload = SecurityHelpers::redact([
            'to' => $to,
            'subject' => $subject,
            'body' => $html,
        ]);

        Log::channel('stack')->info('mail_stub', $payload);
    }
}

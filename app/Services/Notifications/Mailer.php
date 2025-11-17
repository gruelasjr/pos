<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class Mailer
{
    public function send(string $to, string $subject, string $html): void
    {
        Log::channel('stack')->info('mail_stub', [
            'to' => $to,
            'subject' => $subject,
            'body' => $html,
        ]);
    }
}

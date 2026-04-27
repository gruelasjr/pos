<?php

namespace App\Jobs;

use App\Models\OutboxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOutboxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private ?string $messageId = null)
    {
    }

    public function handle(): void
    {
        $query = OutboxMessage::query()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('created_at');

        if ($this->messageId) {
            $query->where('id', $this->messageId);
        }

        $messages = $query->limit(50)->get();

        foreach ($messages as $message) {
            try {
                Log::info('outbox_message_processed', [
                    'id' => $message->id,
                    'event_type' => $message->event_type,
                    'payload' => $message->payload,
                ]);

                $message->status = 'sent';
                $message->processed_at = now();
                $message->attempts += 1;
                $message->save();
            } catch (\Throwable $e) {
                $message->status = 'failed';
                $message->attempts += 1;
                $message->last_error = $e->getMessage();
                $message->save();
            }
        }
    }
}

<?php

namespace App\Domain\Shared;

use App\Models\OutboxMessage;

class OutboxPublisher
{
    public function publish(
        string $eventType,
        array $payload,
        ?string $aggregateType = null,
        ?string $aggregateId = null
    ): OutboxMessage {
        return OutboxMessage::create([
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'status' => 'pending',
            'available_at' => now(),
        ]);
    }
}

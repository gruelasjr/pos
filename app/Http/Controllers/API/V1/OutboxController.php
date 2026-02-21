<?php

namespace App\Http\Controllers\API\V1;

use App\Jobs\ProcessOutboxJob;
use App\Models\OutboxMessage;
use Illuminate\Http\Request;

class OutboxController extends BaseApiController
{
    public function index(Request $request)
    {
        $messages = OutboxMessage::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($messages, 'Outbox listado');
    }

    public function retry(OutboxMessage $outboxMessage)
    {
        $outboxMessage->status = 'pending';
        $outboxMessage->available_at = now();
        $outboxMessage->save();

        ProcessOutboxJob::dispatch($outboxMessage->id);

        return $this->success('Mensaje reprogramado', ['scheduled' => true]);
    }
}

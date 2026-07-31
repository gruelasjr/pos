<?php

namespace App\Jobs;

use App\Models\OutboxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Models\Sale;
use App\Services\Integrations\SaleIntegrationService;
use Throwable;
use Equidna\BeeHive\Tenancy\TenantContext;

class ProcessOutboxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private ?string $messageId = null, private ?string $tenantId = null)
    {
    }

    public function handle(SaleIntegrationService $integrations, TenantContext $tenantContext): void
    {
        $lockToken = (string) Str::uuid();
        $messages = DB::transaction(function () use ($lockToken) {
            OutboxMessage::withoutGlobalScopes()
                ->where('status', 'pending')
                ->whereNotNull('lock_token')
                ->where('locked_at', '<', now()->subMinutes(10))
                ->update(['lock_token' => null, 'locked_at' => null]);
            $query = OutboxMessage::withoutGlobalScopes()
                ->where('status', 'pending')
                ->whereNull('lock_token')
                ->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->orderBy('created_at')
                ->lockForUpdate();
            if ($this->messageId) {
                $query->where('id', $this->messageId);
            }
            if ($this->tenantId) {
                $query->where('tenant_id', $this->tenantId);
            }
            $ids = $query->limit(50)->pluck('id');
            if ($ids->isNotEmpty()) {
                OutboxMessage::withoutGlobalScopes()->whereIn('id', $ids)->update([
                    'lock_token' => $lockToken,
                    'locked_at' => now(),
                ]);
            }
            return OutboxMessage::withoutGlobalScopes()->whereIn('id', $ids)->where('lock_token', $lockToken)->get();
        });

        foreach ($messages as $message) {
            $tenantContext->set((string) $message->tenant_id);
            try {
                $this->deliver($message, $integrations);
                Log::info('outbox_message_processed', [
                    'id' => $message->id,
                    'event_type' => $message->event_type,
                    'payload' => $message->payload,
                ]);

                $message->status = 'sent';
                $message->processed_at = now();
                $message->attempts += 1;
                $message->lock_token = null;
                $message->locked_at = null;
                $message->save();
            } catch (Throwable $e) {
                $message->attempts += 1;
                $message->last_error = $e->getMessage();
                $message->lock_token = null;
                $message->locked_at = null;
                if ($message->attempts >= (int) config('pos_integrations.outbox.max_attempts', 8)) {
                    $message->status = 'failed';
                    $message->dead_lettered_at = now();
                } else {
                    $message->status = 'pending';
                    $message->available_at = now()->addSeconds(min(3600, 2 ** $message->attempts));
                }
                $message->save();
                Log::warning('outbox_message_retry', [
                    'id' => $message->id,
                    'attempts' => $message->attempts,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $tenantContext->clear();
            }
        }
    }

    private function deliver(OutboxMessage $message, SaleIntegrationService $integrations): void
    {
        $saleId = $message->payload['sale_id'] ?? null;
        $sale = $saleId ? Sale::with(['items', 'customer', 'warehouse', 'seller'])->findOrFail($saleId) : null;
        match ($message->event_type) {
            'sale.fiscal_requested' => $integrations->issueFiscalDocument($sale, $message->payload),
            'sale.receipt_requested' => $integrations->printReceipt(
                $sale,
                isset($message->payload['registration_token_encrypted'])
                    ? Crypt::decryptString($message->payload['registration_token_encrypted'])
                    : null
            ),
            'sale.cash_drawer_requested' => $integrations->openCashDrawer($sale),
            'sale.confirmed' => Log::info('sale_confirmed_event', $message->payload),
            default => throw new \RuntimeException("No outbox handler for [{$message->event_type}]."),
        };
    }
}

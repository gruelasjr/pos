<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\Notifications\Mailer;
use App\Services\Notifications\SmsProvider;
use App\Support\ReceiptRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private string $saleId, private array $options = [])
    {
    }

    public function handle(Mailer $mailer, SmsProvider $smsProvider, ReceiptRenderer $renderer): void
    {
        $sale = Sale::with('items', 'customer', 'warehouse', 'seller')->find($this->saleId);

        if (!$sale) {
            throw new RuntimeException('venta_no_encontrada');
        }

        $channel = $this->options['canal'] ?? 'email';
        $destino = $this->options['destino'] ?? $sale->customer?->email;
        $receipt = $renderer->html($sale);

        if ($channel === 'sms') {
            $smsProvider->send($destino ?? '', strip_tags($receipt));
        } else {
            $mailer->send($destino ?? '', 'Recibo de compra ' . $sale->folio, $receipt);
        }

        Log::info('receipt_sent', [
            'sale_id' => $sale->id,
            'channel' => $channel,
        ]);
    }
}

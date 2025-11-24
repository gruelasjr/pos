<?php

/**
 * Job: Send receipt asynchronously.
 *
 * Dispatches a receipt sending job to the queue for background processing.
 *
 * PHP 8.1+
 *
 * @package   App\Jobs
 */

/**
 * Job to send a sale receipt via email or SMS.
 *
 * PHP 8.1+
 *
 * @package   App\Jobs
 */

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
use Equidna\Toolkit\Exceptions\NotFoundException;

/**
 * Dispatchable job that delivers receipts for a sale.
 */
/**
 * Job: send receipt in background.
 *
 * Dispatches a queued job to send sale receipts via configured channels.
 *
 * @package   App\Jobs
 */
class SendReceiptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  string $saleId  Sale identifier.
     * @param  array  $options Delivery options.
     */
    public function __construct(private string $saleId, private array $options = [])
    {
        // No body
    }

    /**
     * Execute the job: render receipt and send via configured channel.
     *
     * @param  Mailer         $mailer
     * @param  SmsProvider    $smsProvider
     * @param  ReceiptRenderer $renderer
     * @return void
     */
    public function handle(Mailer $mailer, SmsProvider $smsProvider, ReceiptRenderer $renderer): void
    {
        $sale = Sale::with('items', 'customer', 'warehouse', 'seller')->find($this->saleId);

        if (!$sale) {
            throw new NotFoundException('venta_no_encontrada');
        }

        $channel = $this->options['channel'] ?? 'email';
        $destination = $this->options['destination'] ?? $sale->customer?->email;
        $receipt = $renderer->html($sale);

        if ($channel === 'sms') {
            $smsProvider->send($destination ?? '', strip_tags($receipt));
        } else {
            $mailer->send($destination ?? '', 'Recibo de compra ' . $sale->folio, $receipt);
        }

        Log::info('receipt_sent', [
            'sale_id' => $sale->id,
            'channel' => $channel,
        ]);
    }
}

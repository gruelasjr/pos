<?php

namespace App\Services\Integrations;

use App\Models\Sale;
use App\Services\Integrations\Exceptions\PaymentDeclinedException;
use App\Support\ReceiptRenderer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;

class SaleIntegrationService
{
    public function __construct(
        private PaymentGateway $payments,
        private FiscalProvider $fiscal,
        private ReceiptPrinter $printer,
        private CashDrawer $cashDrawer,
        private IntegrationEventRecorder $events,
        private ReceiptRenderer $receiptRenderer
    ) {
    }

    public function capturePayment(Sale $sale, array $payload): array
    {
        $request = [
            'sale_id' => $sale->id,
            'folio' => $sale->folio,
            'amount' => (float) $sale->total_net,
            'currency' => 'MXN',
            'method' => $sale->payment_method,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'payment_details' => $payload['payment_details'] ?? null,
        ];

        try {
            $response = $this->payments->charge($request);
        } catch (Throwable $exception) {
            $this->events->failure($sale, 'payment.charge', 'unknown', $request, $exception);
            throw $exception;
        }

        $provider = (string) ($response['provider'] ?? 'unknown');

        if (($response['ok'] ?? false) !== true) {
            $sale->forceFill([
                'payment_status' => (string) ($response['status'] ?? 'declined'),
                'payment_provider' => $provider,
            ])->save();

            $this->events->failure($sale, 'payment.charge', $provider, $request, 'payment_declined', $response);

            throw new PaymentDeclinedException($request, $response, $provider);
        }

        $sale->forceFill([
            'payment_status' => (string) ($response['status'] ?? 'approved'),
            'payment_provider' => $provider,
            'payment_reference' => $response['reference'] ?? $response['transaction_id'] ?? null,
            'payment_authorization_code' => $response['authorization_code'] ?? null,
            'payment_authorized_at' => now(),
        ])->save();

        $this->events->success($sale, 'payment.charge', $provider, $request, $response);

        return $response;
    }

    public function issueFiscalDocument(Sale $sale, array $payload = []): ?array
    {
        if (!$this->shouldIssueFiscalDocument($payload)) {
            $sale->forceFill(['fiscal_status' => 'not_requested'])->save();

            return null;
        }

        $sale->loadMissing('items', 'customer', 'warehouse', 'seller');

        $request = [
            'sale_id' => $sale->id,
            'folio' => $sale->folio,
            'amount' => (float) $sale->total_net,
            'customer' => $payload['fiscal']['customer'] ?? null,
            'items' => $sale->items->map(fn ($item) => [
                'sku' => $item->sku,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ])->all(),
        ];

        $sale->forceFill(['fiscal_status' => 'pending'])->save();
        $response = $this->fiscal->issueInvoice($request);
        $provider = (string) ($response['provider'] ?? 'unknown');

        if (($response['ok'] ?? false) !== true) {
            $sale->forceFill([
                'fiscal_status' => (string) ($response['status'] ?? 'failed'),
                'fiscal_provider' => $provider,
            ])->save();

            $this->events->failure($sale, 'fiscal.issue', $provider, $request, 'fiscal_failed', $response);

            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'fiscalizacion_fallida',
                'data' => null,
                'error' => [
                    'message' => 'fiscalizacion_fallida',
                    'details' => ['fiscal' => ['fiscalizacion_fallida']],
                ],
            ], 422));
        }

        $sale->forceFill([
            'fiscal_status' => (string) ($response['status'] ?? 'issued'),
            'fiscal_provider' => $provider,
            'fiscal_reference' => $response['reference'] ?? $response['invoice_id'] ?? null,
            'fiscal_uuid' => $response['uuid'] ?? null,
            'fiscal_issued_at' => now(),
        ])->save();

        $this->events->success($sale, 'fiscal.issue', $provider, $request, $response);

        return $response;
    }

    public function printReceipt(Sale $sale, ?string $customerRegistrationToken = null): ?array
    {
        if (!config('pos_integrations.receipt_printer.print_on_checkout', true)) {
            $sale->forceFill(['receipt_print_status' => 'skipped'])->save();

            return null;
        }

        $sale->loadMissing('items', 'customer', 'warehouse', 'seller');

        $request = [
            'sale_id' => $sale->id,
            'folio' => $sale->folio,
            'total_net' => (float) $sale->total_net,
        ];
        $payload = [
            ...$request,
            'html' => $this->receiptRenderer->html($sale, $customerRegistrationToken),
        ];

        $response = $this->printer->print($payload);
        $provider = (string) ($response['provider'] ?? 'unknown');

        if (($response['ok'] ?? false) !== true) {
            $sale->forceFill(['receipt_print_status' => 'failed'])->save();
            $this->events->failure($sale, 'receipt.print', $provider, $request, 'receipt_print_failed', $response);

            return $response;
        }

        $sale->forceFill([
            'receipt_print_status' => (string) ($response['status'] ?? 'printed'),
            'receipt_printed_at' => now(),
        ])->save();

        $this->events->success($sale, 'receipt.print', $provider, $request, $response);

        return $response;
    }

    public function openCashDrawer(Sale $sale): ?array
    {
        $shouldOpen = config('pos_integrations.cash_drawer.open_on_cash_checkout', true)
            && in_array($sale->payment_method, ['cash', 'mixed'], true);

        if (!$shouldOpen) {
            $sale->forceFill(['cash_drawer_status' => 'skipped'])->save();

            return null;
        }

        $request = [
            'sale_id' => $sale->id,
            'folio' => $sale->folio,
            'amount' => (float) $sale->total_net,
        ];
        $response = $this->cashDrawer->open($request);
        $provider = (string) ($response['provider'] ?? 'unknown');

        if (($response['ok'] ?? false) !== true) {
            $sale->forceFill(['cash_drawer_status' => 'failed'])->save();
            $this->events->failure($sale, 'cash_drawer.open', $provider, $request, 'cash_drawer_failed', $response);

            return $response;
        }

        $sale->forceFill([
            'cash_drawer_status' => (string) ($response['status'] ?? 'opened'),
            'cash_drawer_opened_at' => now(),
        ])->save();

        $this->events->success($sale, 'cash_drawer.open', $provider, $request, $response);

        return $response;
    }

    private function shouldIssueFiscalDocument(array $payload): bool
    {
        return (bool) (
            $payload['fiscal']['issue_invoice']
            ?? config('pos_integrations.fiscal.issue_on_checkout', false)
        );
    }
}

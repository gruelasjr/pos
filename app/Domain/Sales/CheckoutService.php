<?php

namespace App\Domain\Sales;

use App\Domain\Inventory\InventoryService;
use App\Domain\POS\CashSessionService;
use App\Domain\Shared\OutboxPublisher;
use App\Models\Cart;
use App\Models\CashSession;
use App\Models\PaymentAttempt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Integrations\Exceptions\PaymentDeclinedException;
use App\Services\Integrations\SaleIntegrationService;
use App\Support\FolioGenerator;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/** Durable checkout: local intent is committed before any provider is called. */
class CheckoutService
{
    public function __construct(
        private DatabaseManager $db,
        private InventoryService $inventoryService,
        private FolioGenerator $folioGenerator,
        private CashSessionService $cashSessionService,
        private LoyaltyService $loyaltyService,
        private OutboxPublisher $outboxPublisher,
        private SaleIntegrationService $integrations,
    ) {
    }

    public function checkout(Cart $cart, array $payload): Sale
    {
        if (!$cart->items()->exists()) {
            throw new UnprocessableEntityException('carrito_vacio');
        }
        $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));
        if ($idempotencyKey === '') {
            throw new UnprocessableEntityException('idempotency_key_requerida');
        }

        [$sale, $attempt, $registrationToken] = $this->prepare($cart, $payload, $idempotencyKey);
        if ($attempt->status === 'paid') {
            return $sale->load('items', 'customer', 'seller', 'warehouse');
        }

        try {
            $response = $this->integrations->capturePayment($sale, [
                ...$payload,
                'idempotency_key' => $attempt->idempotency_key,
            ]);
        } catch (Throwable $exception) {
            $definitive = $exception instanceof PaymentDeclinedException;
            $attempt->forceFill([
                'status' => $definitive ? 'failed' : 'reconciliation_required',
                'failure_code' => $definitive ? 'payment_declined' : 'provider_result_unknown',
                'failure_message' => $exception->getMessage(),
                'resolved_at' => $definitive ? now() : null,
            ])->save();
            $sale->forceFill(['payment_status' => $attempt->status])->save();
            if ($definitive) {
                throw new HttpResponseException(response()->json([
                    'success' => false, 'message' => 'pago_rechazado', 'data' => null,
                    'error' => ['message' => 'pago_rechazado', 'details' => ['payment' => ['pago_rechazado']]],
                ], 422));
            }
            throw $exception;
        }

        $attempt->forceFill([
            'status' => 'paid',
            'provider' => $response['provider'] ?? 'unknown',
            'provider_reference' => $response['reference'] ?? $response['transaction_id'] ?? null,
            'response_payload' => $response,
            'attempted_at' => now(),
            'resolved_at' => now(),
        ])->save();

        return $this->finalize($cart->id, $sale->id, $registrationToken, $payload);
    }

    /** @return array{Sale, PaymentAttempt, string} */
    private function prepare(Cart $cart, array $payload, string $idempotencyKey): array
    {
        return $this->db->transaction(function () use ($cart, $payload, $idempotencyKey): array {
            $cart = Cart::query()->lockForUpdate()->with(['items.product', 'warehouse'])->findOrFail($cart->id);
            $existing = PaymentAttempt::query()->where('idempotency_key', $idempotencyKey)
                ->whereHas('sale', fn ($q) => $q->where('user_id', $cart->user_id))->first();
            if ($existing) {
                /** @var Sale $existingSale */
                $existingSale = $existing->sale;
                return [$existingSale, $existing, $existingSale->issueCustomerRegistrationToken()];
            }
            if ($cart->status !== 'active' || $cart->items->isEmpty()) {
                throw new UnprocessableEntityException('carrito_no_disponible');
            }
            foreach ($cart->items as $item) {
                $this->inventoryService->assertSufficient($item->product, $cart->warehouse, $item->quantity);
            }
            $sale = Sale::create([
                'folio' => $this->folioGenerator->next($cart->warehouse),
                'warehouse_id' => $cart->warehouse_id, 'user_id' => $cart->user_id,
                'customer_id' => $payload['customer_id'] ?? null,
                'cash_session_id' => CashSession::query()
                    ->where('user_id', $cart->user_id)
                    ->where('warehouse_id', $cart->warehouse_id)
                    ->where('status', 'open')
                    ->value('id'),
                'payment_method' => $payload['payment_method'],
                'payment_details' => $payload['payment_details'] ?? null,
                'payment_status' => 'pending', 'total_gross' => $cart->total_gross,
                'discount_total' => $cart->discount_total, 'total_net' => $cart->total_net, 'paid_at' => now(),
            ]);
            foreach ($cart->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id, 'product_id' => $item->product_id, 'sku' => $item->product->sku,
                    'description' => $item->product->short_description, 'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price, 'discount' => $item->discount, 'subtotal' => $item->subtotal,
                ]);
            }
            $token = $sale->issueCustomerRegistrationToken();
            $attempt = PaymentAttempt::create([
                'sale_id' => $sale->id, 'idempotency_key' => $idempotencyKey,
                'method' => $payload['payment_method'], 'amount' => $sale->total_net, 'currency' => 'MXN',
                'status' => 'pending', 'request_payload' => ['payment_details' => $payload['payment_details'] ?? null],
            ]);
            return [$sale, $attempt, $token];
        });
    }

    private function finalize(string $cartId, string $saleId, string $registrationToken, array $payload): Sale
    {
        return $this->db->transaction(function () use ($cartId, $saleId, $registrationToken, $payload) {
            $cart = Cart::query()->lockForUpdate()->with(['items.product', 'warehouse'])->findOrFail($cartId);
            $sale = Sale::query()->lockForUpdate()->findOrFail($saleId);
            if ($sale->payment_status === 'paid') {
                return $sale->load('items', 'customer', 'seller', 'warehouse');
            }
            foreach ($cart->items as $item) {
                $this->inventoryService->assertSufficient($item->product, $cart->warehouse, $item->quantity);
                $this->inventoryService->adjust($item->product_id, $cart->warehouse_id, -$item->quantity);
            }
            $sale->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
            $cart->forceFill(['status' => 'closed'])->save();
            $cart->items()->delete();
            if ($sale->cash_session_id) {
                $this->cashSessionService->registerMovement(
                    $sale->cash_session_id,
                    'sale',
                    (float) $sale->total_net,
                    Sale::class,
                    $sale->id,
                    'checkout'
                );
            }
            $this->loyaltyService->accrueFromSale($sale);
            $base = ['sale_id' => $sale->id, 'folio' => $sale->folio, 'total_net' => $sale->total_net];
            $this->outboxPublisher->publish('sale.confirmed', $base, Sale::class, $sale->id);
            if (($payload['fiscal']['issue_invoice'] ?? config('pos_integrations.fiscal.issue_on_checkout', false))) {
                $this->outboxPublisher->publish(
                    'sale.fiscal_requested',
                    [...$base, 'fiscal' => $payload['fiscal'] ?? []],
                    Sale::class,
                    $sale->id
                );
            }
            $this->outboxPublisher->publish(
                'sale.receipt_requested',
                [...$base, 'registration_token_encrypted' => Crypt::encryptString($registrationToken)],
                Sale::class,
                $sale->id
            );
            if (in_array($sale->payment_method, ['cash', 'mixed'], true)) {
                $this->outboxPublisher->publish('sale.cash_drawer_requested', $base, Sale::class, $sale->id);
            }
            $sale = $sale->refresh()->load('items', 'customer', 'seller', 'warehouse');
            $sale->plainCustomerRegistrationToken = $registrationToken;
            return $sale;
        });
    }
}

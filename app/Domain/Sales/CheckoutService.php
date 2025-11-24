<?php

/**
 * Service: Checkout orchestration.
 *
 * Coordinates sale creation, payment processing and post-sale side effects.
 *
 * PHP 8.1+
 *
 * @package   App\Domain\Sales
 */

namespace App\Domain\Sales;

use App\Domain\Inventory\InventoryService;
use App\Jobs\SendReceiptJob;
use App\Models\Cart;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\FolioGenerator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

/**
 * Service: checkout orchestration.
 *
 * Orchestrates checkout, payment application, inventory adjustments and sale persistence.
 *
 * @package   App\Domain\Sales
 */
class CheckoutService
{
    public function __construct(
        private DatabaseManager $db,
        private InventoryService $inventoryService,
        private FolioGenerator $folioGenerator
    ) {}

    public function checkout(Cart $cart, array $payload): Sale
    {
        if ($cart->items()->count() === 0) {
            throw new UnprocessableEntityException('carrito_vacio');
        }

        return $this->db->transaction(function () use ($cart, $payload) {
            $cart->load(['items.product', 'warehouse', 'seller']);

            foreach ($cart->items as $item) {
                $this->inventoryService->assertSufficient($item->product, $cart->warehouse, $item->quantity);
            }

            foreach ($cart->items as $item) {
                $this->inventoryService->adjust($item->product_id, $cart->warehouse_id, -1 * $item->quantity);
            }

            $folio = $this->folioGenerator->next($cart->warehouse);

            /** @var Sale $sale */
            $sale = Sale::create([
                'folio' => $folio,
                'warehouse_id' => $cart->warehouse_id,
                'user_id' => $cart->user_id,
                'customer_id' => $payload['customer_id'] ?? null,
                'payment_method' => $payload['payment_method'],
                'payment_details' => $payload['payment_details'] ?? null,
                'total_gross' => $cart->total_gross,
                'discount_total' => $cart->discount_total,
                'total_net' => $cart->total_net,
                'paid_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'description' => $item->product->short_description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $cart->status = 'closed';
            $cart->save();
            $cart->items()->delete();

            SendReceiptJob::dispatch($sale->id, $payload['receipt'] ?? []);

            return $sale->load('items', 'customer', 'seller', 'warehouse');
        });
    }
}

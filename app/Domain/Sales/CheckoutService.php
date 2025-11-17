<?php

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
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private DatabaseManager $db,
        private InventoryService $inventoryService,
        private FolioGenerator $folioGenerator
    ) {
    }

    public function checkout(Cart $cart, array $payload): Sale
    {
        if ($cart->items()->count() === 0) {
            throw new RuntimeException('carrito_vacio');
        }

        return $this->db->transaction(function () use ($cart, $payload) {
            $cart->load(['items.product', 'warehouse', 'seller']);

            foreach ($cart->items as $item) {
                $this->inventoryService->assertSufficient($item->product, $cart->warehouse, $item->cantidad);
            }

            foreach ($cart->items as $item) {
                $this->inventoryService->adjust($item->product_id, $cart->warehouse_id, -1 * $item->cantidad);
            }

            $folio = $this->folioGenerator->next($cart->warehouse);

            /** @var Sale $sale */
            $sale = Sale::create([
                'folio' => $folio,
                'warehouse_id' => $cart->warehouse_id,
                'user_id' => $cart->user_id,
                'customer_id' => $payload['cliente_id'] ?? null,
                'metodo_pago' => $payload['metodo_pago'],
                'pagos_detalle' => $payload['pagos_detalle'] ?? null,
                'total_bruto' => $cart->total_bruto,
                'descuento_total' => $cart->descuento_total,
                'total_neto' => $cart->total_neto,
                'pagado_en' => now(),
            ]);

            foreach ($cart->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'descripcion' => $item->product->descripcion_corta,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento' => $item->descuento,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $cart->estado = 'cerrado';
            $cart->save();
            $cart->items()->delete();

            SendReceiptJob::dispatch($sale->id, $payload['recibo'] ?? []);

            return $sale->load('items', 'customer', 'seller', 'warehouse');
        });
    }
}

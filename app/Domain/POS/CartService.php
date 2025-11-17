<?php

namespace App\Domain\POS;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

class CartService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function createCart(User $seller, Warehouse $warehouse): Cart
    {
        $cart = Cart::create([
            'user_id' => $seller->id,
            'warehouse_id' => $warehouse->id,
        ]);

        return $cart->fresh()->load('warehouse', 'items.product', 'seller');
    }

    public function addItem(Cart $cart, Product $product, int $cantidad, ?float $precio = null, ?float $descuento = null): Cart
    {
        return $this->db->transaction(function () use ($cart, $product, $cantidad, $precio, $descuento) {
            /** @var CartItem|null $item */
            $item = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();

            if ($item) {
                $item->cantidad += $cantidad;
            } else {
                $item = $cart->items()->make([
                    'product_id' => $product->id,
                    'cantidad' => $cantidad,
                ]);
            }

            $item->precio_unitario = $precio ?? $product->precio_venta;
            $item->descuento = $descuento ?? $item->descuento ?? 0;
            $item->computeSubtotal();
            $item->save();

            $cart->load('items');
            $cart->recalculateTotals();
            $cart->save();

            return $cart->refresh()->load('items.product', 'warehouse', 'seller');
        });
    }

    public function updateItem(Cart $cart, string $itemId, array $payload): Cart
    {
        return $this->db->transaction(function () use ($cart, $itemId, $payload) {
            /** @var CartItem $item */
            $item = $cart->items()->lockForUpdate()->findOrFail($itemId);

            if (isset($payload['cantidad'])) {
                $item->cantidad = (int) $payload['cantidad'];
            }

            if (isset($payload['descuento'])) {
                $item->descuento = (float) $payload['descuento'];
            }

            if (isset($payload['precio_unitario'])) {
                $item->precio_unitario = (float) $payload['precio_unitario'];
            }

            if ($item->cantidad <= 0) {
                throw new RuntimeException('cantidad_invalida');
            }

            $item->computeSubtotal();
            $item->save();

            $cart->load('items');
            $cart->recalculateTotals();
            $cart->save();

            return $cart->refresh()->load('items.product', 'warehouse', 'seller');
        });
    }

    public function removeItem(Cart $cart, string $itemId): Cart
    {
        return $this->db->transaction(function () use ($cart, $itemId) {
            $cart->items()->where('id', $itemId)->delete();
            $cart->load('items');
            $cart->recalculateTotals();
            $cart->save();

            return $cart->refresh()->load('items.product', 'warehouse', 'seller');
        });
    }

    public function updateCart(Cart $cart, array $payload): Cart
    {
        $cart->fill($payload);
        $cart->recalculateTotals();
        $cart->save();

        return $cart->refresh()->load('items.product', 'warehouse', 'seller');
    }
}

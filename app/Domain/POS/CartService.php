<?php

namespace App\Domain\POS;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
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

    public function addItem(Cart $cart, Product $product, int $quantity, ?float $price = null, ?float $discount = null): Cart
    {
        return $this->db->transaction(function () use ($cart, $product, $quantity, $price, $discount) {
            /** @var CartItem|null $item */
            $item = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();

            if ($item) {
                $item->quantity += $quantity;
            } else {
                $item = $cart->items()->make([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            }

            $item->unit_price = $price ?? $product->sale_price;
            $item->discount = $discount ?? $item->discount ?? 0;
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

            if (isset($payload['quantity'])) {
                $item->quantity = (int) $payload['quantity'];
            }

            if (isset($payload['discount'])) {
                $item->discount = (float) $payload['discount'];
            }

            if (isset($payload['unit_price'])) {
                $item->unit_price = (float) $payload['unit_price'];
            }

            if ($item->quantity <= 0) {
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

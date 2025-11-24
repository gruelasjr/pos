<?php

/**
 * Service: Cart domain service.
 *
 * Coordinates cart lifecycle operations used by the POS.
 *
 * PHP 8.1+
 *
 * @package   App\Domain\POS
 */

/**
 * Cart service - manages cart lifecycle and item operations.
 *
 * PHP 8.1+
 *
 * @package   App\Domain\POS
 */

namespace App\Domain\POS;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

/**
 * Service that encapsulates cart business logic.
 */
/**
 * Service to manage cart lifecycle and item operations.
 *
 * Coordinates creation, mutation and validation of shopping carts used by the POS.
 *
 * @package   App\Domain\POS
 */
class CartService
{
    public function __construct(private DatabaseManager $db)
    {
        // No body
    }

    /**
     * Create a new cart for the given seller and warehouse.
     *
     * @param  User      $seller    Seller user who owns the cart.
     * @param  Warehouse $warehouse Warehouse where the cart is located.
     * @return Cart
     */
    public function createCart(User $seller, Warehouse $warehouse): Cart
    {
        $cart = Cart::create([
            'user_id' => $seller->id,
            'warehouse_id' => $warehouse->id,
        ]);

        return $cart->fresh()->load('warehouse', 'items.product', 'seller');
    }

    /**
     * Add an item to a cart or increment quantity if exists.
     *
     * @param  Cart         $cart
     * @param  Product      $product
     * @param  int          $quantity
     * @param  float|null   $price
     * @param  float|null   $discount
     * @return Cart
     */
    public function addItem(Cart $cart, Product $product, int $quantity, ?float $price = null, ?float $discount = null): Cart
    {
        return $this->db->transaction(function () use ($cart, $product, $quantity, $price, $discount) {
            /** @var CartItem|null $item */
            $item = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();

            if ($item) {
                $item->quantity += $quantity;
            } else {
                /** @var CartItem $item */
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

            $cart = $cart->refresh();
            $cart->load('items.product', 'warehouse', 'seller');

            return $cart;
        });
    }

    /**
     * Update an existing cart item by id.
     *
     * @param  Cart   $cart
     * @param  string $itemId
     * @param  array  $payload
     * @return Cart
     */
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
                throw new UnprocessableEntityException('cantidad_invalida');
            }

            $item->computeSubtotal();
            $item->save();

            $cart->load('items');
            $cart->recalculateTotals();
            $cart->save();

            $cart = $cart->refresh();
            $cart->load('items.product', 'warehouse', 'seller');

            return $cart;
        });
    }

    /**
     * Remove an item from the cart.
     *
     * @param  Cart   $cart
     * @param  string $itemId
     * @return Cart
     */
    public function removeItem(Cart $cart, string $itemId): Cart
    {
        return $this->db->transaction(function () use ($cart, $itemId) {
            $cart->items()->where('id', $itemId)->delete();
            $cart->load('items');
            $cart->recalculateTotals();
            $cart->save();

            $cart = $cart->refresh();
            $cart->load('items.product', 'warehouse', 'seller');

            return $cart;
        });
    }

    /**
     * Update cart metadata and recalculate totals.
     *
     * @param  Cart  $cart
     * @param  array $payload
     * @return Cart
     */
    public function updateCart(Cart $cart, array $payload): Cart
    {
        $cart->fill($payload);
        $cart->recalculateTotals();
        $cart->save();

        $cart = $cart->refresh();
        $cart->load('items.product', 'warehouse', 'seller');

        return $cart;
    }
}

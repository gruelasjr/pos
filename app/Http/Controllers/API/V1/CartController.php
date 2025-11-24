<?php

/**
 * Controller: Cart management endpoints (API v1).
 *
 * Handles cart creation, updates and retrieval for the point-of-sale API.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * Cart API controller.
 *
 * Handles cart CRUD and checkout endpoints for API v1.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Domain\POS\CartService;
use App\Domain\Sales\CheckoutService;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Equidna\Toolkit\Exceptions\ForbiddenException;

/**
 * Controller exposing cart management endpoints.
 */
/**
 * Cart controller.
 *
 * Provides cart management endpoints (create, update, checkout) for the API.
 *
 * @package   App\Http\Controllers\API\V1
 */
class CartController extends BaseApiController
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService
    ) {
        //
    }

    /**
     * List carts with pagination and optional filters.
     *
     * @param  Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $carts = Cart::query()
            ->with('items.product', 'warehouse', 'seller')
            ->when(!$user->isAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('updated_at', 'desc')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($carts, 'Carritos listados');
    }

    /**
     * Create a new cart for the authenticated user.
     *
     * @param  Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $cart = $this->cartService->createCart($request->user(), Warehouse::findOrFail($data['warehouse_id']));

        return $this->success('Carrito creado', $cart->load('warehouse'));
    }

    /**
     * Add a product to a cart.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @return mixed
     */
    public function addItem(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $this->cartService->addItem(
            $cart,
            Product::findOrFail($data['product_id']),
            $data['quantity'],
            $data['unit_price'] ?? null,
            $data['discount'] ?? null
        );

        return $this->success('Producto agregado al carrito', $cart);
    }

    /**
     * Update an item quantity/price in a cart.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @param  string  $itemId
     * @return mixed
     */
    public function updateItem(Request $request, Cart $cart, string $itemId)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $this->cartService->updateItem($cart, $itemId, $data);

        return $this->success('Producto actualizado en el carrito', $cart);
    }

    /**
     * Remove an item from the cart.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @param  string  $itemId
     * @return mixed
     */
    public function deleteItem(Request $request, Cart $cart, string $itemId)
    {
        $this->authorizeCart($request, $cart);

        $cart = $this->cartService->removeItem($cart, $itemId);

        return $this->success('Producto eliminado del carrito', $cart);
    }

    /**
     * Update cart-level fields such as discount or status.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @return mixed
     */
    public function updateCart(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $cart = $this->cartService->updateCart($cart, $data);

        return $this->success('Carrito actualizado', $cart);
    }

    /**
     * Checkout a cart and create a sale.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @return mixed
     */
    public function checkout(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer', 'mixed'])],
            'payment_details' => ['nullable', 'array'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'receipt' => ['nullable', 'array'],
        ]);

        $sale = $this->checkoutService->checkout($cart, $data);

        return $this->success('Venta confirmada', $sale);
    }

    /**
     * Ensure the authenticated user is allowed to act on the cart.
     *
     * @param  Request $request
     * @param  Cart    $cart
     * @return void
     */
    protected function authorizeCart(Request $request, Cart $cart): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $cart->user_id === $user->id) {
            return;
        }

        throw new ForbiddenException('No autorizado');
    }
}

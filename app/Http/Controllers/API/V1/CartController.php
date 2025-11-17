<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\POS\CartService;
use App\Domain\Sales\CheckoutService;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends BaseApiController
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $carts = Cart::query()
            ->with('items.product', 'warehouse', 'seller')
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->orderBy('updated_at', 'desc')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($carts, 'Carritos listados');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'almacen_id' => ['required', 'exists:warehouses,id'],
        ]);

        $cart = $this->cartService->createCart($request->user(), Warehouse::findOrFail($data['almacen_id']));

        return $this->success('Carrito creado', $cart->load('warehouse'));
    }

    public function addItem(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'producto_id' => ['required', 'exists:products,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $this->cartService->addItem(
            $cart,
            Product::findOrFail($data['producto_id']),
            $data['cantidad'],
            $data['precio_unitario'] ?? null,
            $data['descuento'] ?? null
        );

        return $this->success('Producto agregado al carrito', $cart);
    }

    public function updateItem(Request $request, Cart $cart, string $itemId)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'cantidad' => ['nullable', 'integer', 'min:1'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $this->cartService->updateItem($cart, $itemId, $data);

        return $this->success('Producto actualizado en el carrito', $cart);
    }

    public function deleteItem(Request $request, Cart $cart, string $itemId)
    {
        $this->authorizeCart($request, $cart);

        $cart = $this->cartService->removeItem($cart, $itemId);

        return $this->success('Producto eliminado del carrito', $cart);
    }

    public function updateCart(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'descuento_total' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', Rule::in(['activo', 'en_pausa', 'cerrado'])],
        ]);

        $cart = $this->cartService->updateCart($cart, $data);

        return $this->success('Carrito actualizado', $cart);
    }

    public function checkout(Request $request, Cart $cart)
    {
        $this->authorizeCart($request, $cart);

        $data = $request->validate([
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'mixto'])],
            'pagos_detalle' => ['nullable', 'array'],
            'cliente_id' => ['nullable', 'exists:customers,id'],
            'recibo' => ['nullable', 'array'],
        ]);

        $sale = $this->checkoutService->checkout($cart, $data);

        return $this->success('Venta confirmada', $sale);
    }

    protected function authorizeCart(Request $request, Cart $cart): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $cart->user_id === $user->id) {
            return;
        }

        abort(403, 'No autorizado');
    }
}

<?php

/**
 * Controller: Product endpoints (API v1).
 *
 * Handles product listing, retrieval and catalog operations for the POS API.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for product catalog management.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Domain\Catalog\SkuGeneratorService;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Controller for product catalog endpoints.
 */
/**
 * Product controller.
 *
 * Provides product catalog endpoints for listing and management.
 *
 * @package   App\Http\Controllers\API\V1
 */
class ProductController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with('type')
            ->search($request->string('query'))
            ->when(
                $request->filled('product_type_id'),
                fn($q) => $q->where('product_type_id', $request->input('product_type_id'))
            )
            ->when($request->filled('warehouse_id'), function ($q) use ($request) {
                $warehouseId = $request->input('warehouse_id');
                $q->withSum(
                    ['inventories as stock' => fn($sub) => $sub->where('warehouse_id', $warehouseId)],
                    'stock'
                );
            })
            ->orderBy('short_description');

        $products = $query->paginate($request->integer('per_page', 25));

        return $this->paginated($products, 'Productos listados');
    }

    public function store(Request $request, SkuGeneratorService $skuGenerator)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:64', 'unique:products,sku'],
            'short_description' => ['required', 'string', 'max:160'],
            'long_description' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'entry_date' => ['required', 'date'],
            'product_type_id' => ['required', 'exists:product_types,id'],
            'active' => ['boolean'],
        ]);

        if (empty($data['sku'])) {
            $data['sku'] = $skuGenerator->reserve(1)['skus'][0];
        }

        $product = Product::create($data);

        return $this->success('Producto creado', $product->load('type'));
    }

    public function show(Product $product)
    {
        return $this->success('Detalle de producto', $product->load('type', 'inventories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'short_description' => ['sometimes', 'string', 'max:160'],
            'long_description' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'entry_date' => ['sometimes', 'date'],
            'stock_end_date' => ['nullable', 'date'],
            'product_type_id' => ['sometimes', 'exists:product_types,id'],
            'active' => ['boolean'],
        ]);

        $product->update($data);

        return $this->success('Producto actualizado', $product->load('type'));
    }
}

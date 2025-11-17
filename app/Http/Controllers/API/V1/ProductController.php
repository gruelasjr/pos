<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Catalog\SkuGeneratorService;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with('type')
            ->search($request->string('query'))
            ->when($request->filled('tipo_id'), fn ($q) => $q->where('product_type_id', $request->input('tipo_id')))
            ->when($request->filled('almacen_id'), function ($q) use ($request) {
                $warehouseId = $request->input('almacen_id');
                $q->withSum(['inventories as existencias' => fn ($sub) => $sub->where('warehouse_id', $warehouseId)], 'existencias');
            })
            ->orderBy('descripcion_corta');

        $products = $query->paginate($request->integer('per_page', 25));

        return $this->paginated($products);
    }

    public function store(Request $request, SkuGeneratorService $skuGenerator)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:64', 'unique:products,sku'],
            'descripcion_corta' => ['required', 'string', 'max:160'],
            'descripcion_larga' => ['nullable', 'string'],
            'foto_url' => ['nullable', 'string'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'fecha_ingreso' => ['required', 'date'],
            'product_type_id' => ['required', 'exists:product_types,id'],
            'activo' => ['boolean'],
        ]);

        if (empty($data['sku'])) {
            $data['sku'] = $skuGenerator->reserve(1)['skus'][0];
        }

        $product = Product::create($data);

        return $this->success($product->load('type'));
    }

    public function show(Product $product)
    {
        return $this->success($product->load('type', 'inventories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'descripcion_corta' => ['sometimes', 'string', 'max:160'],
            'descripcion_larga' => ['nullable', 'string'],
            'foto_url' => ['nullable', 'string'],
            'precio_compra' => ['sometimes', 'numeric', 'min:0'],
            'precio_venta' => ['sometimes', 'numeric', 'min:0'],
            'fecha_ingreso' => ['sometimes', 'date'],
            'fecha_fin_stock' => ['nullable', 'date'],
            'product_type_id' => ['sometimes', 'exists:product_types,id'],
            'activo' => ['boolean'],
        ]);

        $product->update($data);

        return $this->success($product->load('type'));
    }
}

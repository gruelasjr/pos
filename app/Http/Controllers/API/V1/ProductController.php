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
use App\Models\ProductMetadataValue;
use App\Services\Catalog\ProductTaxonomyService;
use App\Services\Catalog\SkuRangeService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            ->with('type', 'tags', 'metadataValues.definition')
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
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') !== 'all') {
                    $q->where('active', $request->input('status') === 'active');
                }
            })
            ->when($request->filled('tag_ids'), function ($q) use ($request) {
                $tagIds = array_filter((array) $request->input('tag_ids'));
                $q->whereHas('tags', fn ($tags) => $tags->whereIn('product_tags.id', $tagIds));
            })
            ->when($request->filled('metadata'), function ($q) use ($request) {
                foreach ((array) $request->input('metadata') as $key => $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $q->whereHas('metadataValues', function ($values) use ($key, $value) {
                        $values->whereHas('definition', fn ($definitions) => $definitions->where('key', $key))
                            ->where(function ($typed) use ($value) {
                                $typed->where('value_text', (string) $value)
                                    ->orWhere('value_number', is_numeric($value) ? (float) $value : null)
                                    ->orWhere(
                                        'value_boolean',
                                        filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                                    );
                            });
                    });
                }
            })
            ->when($request->input('stock_status') === 'low', function ($q) use ($request) {
                $q->whereHas('inventories', function ($inventory) use ($request) {
                    $inventory->whereColumn('stock', '<=', 'reorder_point');
                    if ($request->filled('warehouse_id')) {
                        $inventory->where('warehouse_id', $request->input('warehouse_id'));
                    }
                });
            })
            ->when(in_array($request->input('stock_status'), ['in_stock', 'out'], true), function ($q) use ($request) {
                $operator = $request->input('stock_status') === 'out' ? '<=' : '>';
                $q->whereHas('inventories', function ($inventory) use ($request, $operator) {
                    $inventory->where('stock', $operator, 0);
                    if ($request->filled('warehouse_id')) {
                        $inventory->where('warehouse_id', $request->input('warehouse_id'));
                    }
                });
            })
            ->orderBy('short_description');

        $products = $query->paginate($request->integer('per_page', 25));

        return $this->paginated($products, 'Productos listados');
    }

    public function store(
        Request $request,
        SkuGeneratorService $skuGenerator,
        AuditLogger $auditLogger,
        ProductTaxonomyService $taxonomy,
        SkuRangeService $skuRanges
    ) {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:64', 'unique:products,sku'],
            'short_description' => ['required', 'string', 'max:160'],
            'long_description' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'url', 'max:2048', 'starts_with:https://'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'entry_date' => ['required', 'date'],
            'product_type_id' => ['required', 'exists:product_types,id'],
            'active' => ['boolean'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', 'exists:product_tags,id'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $tagIds = $data['tag_ids'] ?? [];
        $metadata = $data['metadata'] ?? [];
        unset($data['tag_ids'], $data['metadata']);

        if (empty($data['sku'])) {
            $data['sku'] = $skuGenerator->reserve(1)['skus'][0];
        }

        if ($range = $skuRanges->match($data['sku'])) {
            $inherited = $skuRanges->inheritedMetadata($range);
            foreach ($inherited as $definitionId => $value) {
                if (array_key_exists($definitionId, $metadata) && (string) $metadata[$definitionId] !== (string) $value) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "metadata.{$definitionId}" => ['El valor está determinado por el rango SKU.'],
                    ]);
                }
            }
            $metadata = array_replace($metadata, $inherited);
        }

        $product = $taxonomy->transaction(function () use ($data, $tagIds, $metadata, $taxonomy) {
            $product = Product::create($data);
            return $taxonomy->sync($product, $tagIds, $metadata);
        });

        $auditLogger->log('product.created', $request->user(), Product::class, $product->id, [
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'product_type_id' => $product->product_type_id,
        ]);

        return $this->success('Producto creado', $product->load('type'));
    }

    public function show(Product $product)
    {
        return $this->success(
            'Detalle de producto',
            $product->load('type', 'tags', 'metadataValues.definition', 'inventories.warehouse')
        );
    }

    public function update(
        Request $request,
        Product $product,
        AuditLogger $auditLogger,
        ProductTaxonomyService $taxonomy
    ) {
        $data = $request->validate([
            'short_description' => ['sometimes', 'string', 'max:160'],
            'long_description' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'url', 'max:2048', 'starts_with:https://'],
            'purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'entry_date' => ['sometimes', 'date'],
            'stock_end_date' => ['nullable', 'date'],
            'product_type_id' => ['sometimes', 'exists:product_types,id'],
            'active' => ['boolean'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', 'exists:product_tags,id'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $tagIds = $data['tag_ids'] ?? $product->tags()->pluck('product_tags.id')->all();
        $metadata = $data['metadata'] ?? $product->metadataValues()->with('definition')->get()->mapWithKeys(
            fn (ProductMetadataValue $value) => [$value->definition_id => $value->resolvedValue()]
        )->all();
        unset($data['tag_ids'], $data['metadata']);

        $product = $taxonomy->transaction(function () use ($product, $data, $tagIds, $metadata, $taxonomy) {
            $product->update($data);
            return $taxonomy->sync($product, $tagIds, $metadata);
        });

        $auditLogger->log('product.updated', $request->user(), Product::class, $product->id, [
            'changes' => $data,
        ]);

        return $this->success('Producto actualizado', $product->load('type'));
    }

    public function uploadPhoto(Request $request, Product $product)
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $oldPath = $this->managedPhotoPath($product->photo_url);
        $path = $request->file('photo')->storePublicly('products/' . $product->getAttribute('tenant_id'), 'public');
        $product->update(['photo_url' => Storage::disk('public')->url($path)]);
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->success('Foto actualizada', $product->fresh()->load('type', 'tags', 'metadataValues.definition'));
    }

    private function managedPhotoPath(?string $url): ?string
    {
        if (! $url || ! str_contains($url, '/storage/products/')) {
            return null;
        }

        return 'products/' . explode('/storage/products/', $url, 2)[1];
    }
}

<?php

/**
 * Controller: Inventory endpoints (API v1).
 *
 * Provides inventory queries and adjustments for warehouses and products.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for inventory endpoints and adjustments.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Domain\Inventory\InventoryService;
use App\Models\Inventory;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

/**
 * Controller for inventory endpoints.
 *
 * Handles listing and adjustment operations for inventory.
 */
/**
 * Inventory controller.
 *
 * Handles inventory queries and adjustments for products and warehouses.
 *
 * @package   App\Http\Controllers\API\V1
 */
class InventoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $inventory = Inventory::query()
            ->with('product.type', 'warehouse')
            ->when($request->filled('query'), function ($query) use ($request) {
                $term = $request->input('query');
                $query->whereHas('product', fn ($product) => $product
                    ->where('short_description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->input('stock_status') === 'low', fn ($query) =>
                $query->whereColumn('stock', '<=', 'reorder_point'))
            ->when($request->input('stock_status') === 'out', fn ($query) => $query->where('stock', '<=', 0))
            ->when($request->input('stock_status') === 'in_stock', fn ($query) => $query->where('stock', '>', 0));

        if ($request->filled('warehouse_id')) {
            $inventory->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $inventory->where('product_id', $request->input('product_id'));
        }

        $inventory = $inventory->paginate($request->integer('per_page', 25));

        return $this->paginated($inventory, 'Inventario listado');
    }

    public function adjust(Request $request, InventoryService $inventoryService, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'delta' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $inventory = $inventoryService->adjust(
            $data['product_id'],
            $data['warehouse_id'],
            $data['delta'],
            $data['reason']
        );

        $auditLogger->log('inventory.adjusted', $request->user(), Inventory::class, $inventory->id, [
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'delta' => $data['delta'],
            'reason' => $data['reason'],
        ]);

        return $this->success('Inventario actualizado', $inventory->load('product', 'warehouse'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate(['reorder_point' => ['required', 'integer', 'min:0']]);
        $inventory->update($data);

        return $this->success('Punto de reorden actualizado', $inventory->load('product', 'warehouse'));
    }
}

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
            ->with('product', 'warehouse');

        if ($request->filled('warehouse_id')) {
            $inventory->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $inventory->where('product_id', $request->input('product_id'));
        }

        $inventory = $inventory->paginate($request->integer('per_page', 25));

        return $this->paginated($inventory, 'Inventario listado');
    }

    public function adjust(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'delta' => ['required', 'integer'],
            'reason' => ['nullable', 'string'],
        ]);

        $inventory = $inventoryService->adjust($data['product_id'], $data['warehouse_id'], $data['delta'], $data['reason'] ?? null);

        return $this->success('Inventario actualizado', $inventory->load('product', 'warehouse'));
    }
}

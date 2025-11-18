<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Inventory\InventoryService;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $inventory = Inventory::query()
            ->with('product', 'warehouse')
            ->when($request->filled('warehouse_id'), fn($q) => $q->where('warehouse_id', $request->input('warehouse_id')))
            ->when($request->filled('product_id'), fn($q) => $q->where('product_id', $request->input('product_id')))
            ->paginate($request->integer('per_page', 25));

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

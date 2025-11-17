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
            ->when($request->filled('almacen_id'), fn ($q) => $q->where('warehouse_id', $request->input('almacen_id')))
            ->when($request->filled('producto_id'), fn ($q) => $q->where('product_id', $request->input('producto_id')))
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($inventory, 'Inventario listado');
    }

    public function adjust(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:products,id'],
            'almacen_id' => ['required', 'exists:warehouses,id'],
            'delta' => ['required', 'integer'],
            'motivo' => ['nullable', 'string'],
        ]);

        $inventory = $inventoryService->adjust($data['producto_id'], $data['almacen_id'], $data['delta'], $data['motivo'] ?? null);

        return $this->success('Inventario actualizado', $inventory->load('product', 'warehouse'));
    }
}

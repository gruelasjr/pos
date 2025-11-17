<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::query()
            ->when($request->boolean('activos'), fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($warehouses);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'codigo' => ['required', 'string', 'max:32', 'unique:warehouses,codigo'],
            'activo' => ['boolean'],
        ]);

        $warehouse = Warehouse::create($data);

        return $this->success($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:120'],
            'codigo' => ['sometimes', 'string', 'max:32', 'unique:warehouses,codigo,' . $warehouse->id . ',id'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update($data);

        return $this->success($warehouse);
    }
}

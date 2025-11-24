<?php

/**
 * Controller: Warehouse endpoints (API v1).
 *
 * Manages warehouses and their settings used by the POS.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for warehouses.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * Controller for warehouse API endpoints.
 *
 * Handles listing, creation and updates of warehouses.
 */
/**
 * Warehouse resource controller.
 *
 * Handles CRUD operations for warehouses in the POS API.
 *
 * @package   App\Http\Controllers\API\V1
 */
class WarehouseController extends BaseApiController
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::query()
            ->when($request->boolean('active'), fn($q) => $q->where('active', true))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($warehouses, 'Almacenes listados');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32', 'unique:warehouses,code'],
            'active' => ['boolean'],
        ]);

        $warehouse = Warehouse::create($data);

        return $this->success('Almacén creado', $warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:32', 'unique:warehouses,code,' . $warehouse->id . ',id'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update($data);

        return $this->success('Almacén actualizado', $warehouse);
    }
}

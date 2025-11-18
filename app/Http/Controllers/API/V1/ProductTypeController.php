<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends BaseApiController
{
    public function index(Request $request)
    {
        $types = ProductType::query()
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return $this->paginated($types, 'Tipos de producto listados');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32', 'unique:product_types,code'],
        ]);

        $type = ProductType::create($data);

        return $this->success('Tipo de producto creado', $type);
    }

    public function update(Request $request, ProductType $productType)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:32', 'unique:product_types,code,' . $productType->id . ',id'],
        ]);

        $productType->update($data);

        return $this->success('Tipo de producto actualizado', $productType);
    }
}

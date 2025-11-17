<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends BaseApiController
{
    public function index(Request $request)
    {
        $types = ProductType::query()
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 50));

        return $this->paginated($types);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'codigo' => ['required', 'string', 'max:32', 'unique:product_types,codigo'],
        ]);

        $type = ProductType::create($data);

        return $this->success($type);
    }

    public function update(Request $request, ProductType $productType)
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:120'],
            'codigo' => ['sometimes', 'string', 'max:32', 'unique:product_types,codigo,' . $productType->id . ',id'],
        ]);

        $productType->update($data);

        return $this->success($productType);
    }
}

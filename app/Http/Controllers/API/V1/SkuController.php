<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Catalog\SkuGeneratorService;
use Illuminate\Http\Request;

class SkuController extends BaseApiController
{
    public function reserve(Request $request, SkuGeneratorService $skuGenerator)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
            'prefijo' => ['nullable', 'string', 'max:16'],
        ]);

        $reservation = $skuGenerator->reserve($data['cantidad'], $data['prefijo'] ?? null);

        return $this->success($reservation);
    }
}

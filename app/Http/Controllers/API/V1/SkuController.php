<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Catalog\SkuGeneratorService;
use Illuminate\Http\Request;

class SkuController extends BaseApiController
{
    public function reserve(Request $request, SkuGeneratorService $skuGenerator)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:16'],
        ]);

        $reservation = $skuGenerator->reserve($data['quantity'], $data['prefix'] ?? null);

        return $this->success('SKUs reservados', $reservation);
    }
}

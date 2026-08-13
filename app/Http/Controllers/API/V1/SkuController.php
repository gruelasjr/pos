<?php

/**
 * Controller: SKU endpoints (API v1).
 *
 * Handles SKU generation and lookup for products.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for SKU reservation endpoints.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Domain\Catalog\SkuGeneratorService;
use App\Models\ReservedSkuRange;
use App\Support\AuditLogger;
use App\Services\Catalog\SkuRangeService;
use Illuminate\Http\Request;

/**
 * Controller exposing SKU reservation endpoints.
 */
/**
 * SKU controller.
 *
 * Provides SKU generation and reservation endpoints for the POS API.
 *
 * @package   App\Http\Controllers\API\V1
 */
class SkuController extends BaseApiController
{
    public function index(Request $request)
    {
        $ranges = ReservedSkuRange::query()
            ->when($request->filled('status') && $request->input('status') !== 'all', fn ($query) =>
                $query->where('active', $request->input('status') === 'active'))
            ->with('segments.definition', 'segments.codedValue')
            ->orderBy('composed_prefix')->orderBy('from')
            ->paginate($request->integer('per_page', 25));

        $ranges->getCollection()->transform(fn (ReservedSkuRange $range) => app(SkuRangeService::class)->present($range));

        return $this->paginated($ranges, 'Rangos SKU listados');
    }

    public function store(Request $request, SkuRangeService $ranges)
    {
        $range = $ranges->save($this->rangePayload($request));

        return $this->success('Rango SKU creado', $range);
    }

    public function update(Request $request, ReservedSkuRange $range, SkuRangeService $ranges)
    {
        $range = $ranges->save($this->rangePayload($request, true), $range);

        return $this->success('Rango SKU actualizado', $range);
    }

    public function reserve(Request $request, SkuGeneratorService $skuGenerator, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:255'],
        ]);

        $reservation = $skuGenerator->reserve($data['quantity'], $data['prefix'] ?? null);

        $auditLogger->log('sku.reserved', $request->user(), ReservedSkuRange::class, $reservation['range_id'], [
            'quantity' => $data['quantity'],
            'prefix' => $data['prefix'] ?? null,
            'skus' => $reservation['skus'],
        ]);

        return $this->success('SKUs reservados', $reservation);
    }

    public function match(Request $request, SkuRangeService $ranges)
    {
        $data = $request->validate(['sku' => ['required', 'string', 'max:64']]);
        $range = $ranges->match($data['sku']);
        return $this->success('Clasificación SKU resuelta', $range ? [
            'range' => $ranges->present($range),
            'metadata' => $ranges->inheritedMetadata($range),
        ] : null);
    }

    private function rangePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'from' => [$required, 'integer', 'min:0'],
            'to' => [$required, 'integer', 'gte:from'],
            'segments' => [$required, 'array', 'min:1'],
            'segments.*.definition_id' => ['required', 'uuid'],
            'segments.*.coded_value_id' => ['required', 'uuid'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }
}

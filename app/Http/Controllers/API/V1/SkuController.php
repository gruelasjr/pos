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
    public function reserve(Request $request, SkuGeneratorService $skuGenerator, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:16'],
        ]);

        $reservation = $skuGenerator->reserve($data['quantity'], $data['prefix'] ?? null);

        $auditLogger->log('sku.reserved', $request->user(), ReservedSkuRange::class, $reservation['range_id'], [
            'quantity' => $data['quantity'],
            'prefix' => $data['prefix'] ?? null,
            'skus' => $reservation['skus'],
        ]);

        return $this->success('SKUs reservados', $reservation);
    }
}

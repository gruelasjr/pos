<?php

/**
 * Controller: Sale endpoints (API v1).
 *
 * Handles sale creation, retrieval and reporting for the POS API.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for sales operations.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Jobs\SendReceiptJob;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller for sales endpoints (list, show, receipt dispatch).
 */
/**
 * Sale controller.
 *
 * Manages sale retrieval, listing and receipt operations via the API.
 *
 * @package   App\Http\Controllers\API\V1
 */
class SaleController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Sale::query()
            ->with('customer', 'seller', 'warehouse');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('seller_id')) {
            $query->where('user_id', $request->input('seller_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->input('to'));
        }

        $sales = $query->orderByDesc('paid_at')->paginate($request->integer('per_page', 25));

        return $this->paginated($sales, 'Ventas listadas');
    }

    public function show(Sale $sale)
    {
        return $this->success('Detalle de venta', $sale->load('items', 'customer', 'warehouse', 'seller'));
    }

    public function sendReceipt(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'destination' => ['required', 'string'],
        ]);

        SendReceiptJob::dispatch($sale->id, $data);

        return $this->success('Recibo programado', ['scheduled' => true]);
    }
}

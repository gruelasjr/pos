<?php

namespace App\Http\Controllers\API\V1;

use App\Jobs\SendReceiptJob;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleController extends BaseApiController
{
    public function index(Request $request)
    {
        $sales = Sale::query()
            ->with('customer', 'seller', 'warehouse')
            ->when($request->filled('warehouse_id'), fn($q) => $q->where('warehouse_id', $request->input('warehouse_id')))
            ->when($request->filled('seller_id'), fn($q) => $q->where('user_id', $request->input('seller_id')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('paid_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn($q) => $q->whereDate('paid_at', '<=', $request->input('to')))
            ->orderByDesc('paid_at')
            ->paginate($request->integer('per_page', 25));

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

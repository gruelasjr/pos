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
            ->when($request->filled('almacen_id'), fn ($q) => $q->where('warehouse_id', $request->input('almacen_id')))
            ->when($request->filled('vendedor_id'), fn ($q) => $q->where('user_id', $request->input('vendedor_id')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('pagado_en', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('pagado_en', '<=', $request->input('hasta')))
            ->orderByDesc('pagado_en')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($sales);
    }

    public function show(Sale $sale)
    {
        return $this->success($sale->load('items', 'customer', 'warehouse', 'seller'));
    }

    public function sendReceipt(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'canal' => ['required', Rule::in(['email', 'sms'])],
            'destino' => ['required', 'string'],
        ]);

        SendReceiptJob::dispatch($sale->id, $data);

        return $this->success(['scheduled' => true]);
    }
}

<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Sales\ReturnService;
use App\Models\ReturnNote;
use App\Models\Sale;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class ReturnController extends BaseApiController
{
    public function index(Request $request)
    {
        $returns = ReturnNote::query()
            ->with('sale', 'items')
            ->when($request->filled('sale_id'), fn($q) => $q->where('sale_id', $request->input('sale_id')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($returns, 'Devoluciones listadas');
    }

    public function show(ReturnNote $return)
    {
        return $this->success('Detalle de devolución', $return->load('sale', 'items'));
    }

    public function store(Request $request, Sale $sale, ReturnService $returnService, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $returnNote = $returnService->createReturn($sale, $request->user(), $data['items'], $data['reason'] ?? null);

        $auditLogger->log('sale.return_created', $request->user(), ReturnNote::class, $returnNote->id, [
            'sale_id' => $sale->id,
            'total_refund' => $returnNote->total_refund,
        ]);

        return $this->success('Devolución registrada', $returnNote);
    }
}

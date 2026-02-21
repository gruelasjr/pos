<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Promotion;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class PromotionController extends BaseApiController
{
    public function index(Request $request)
    {
        $promotions = Promotion::query()
            ->orderBy('priority')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($promotions, 'Promociones listadas');
    }

    public function store(Request $request, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'stackable' => ['boolean'],
            'active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $promotion = Promotion::create($data);

        $auditLogger->log('promotion.created', $request->user(), Promotion::class, $promotion->id, $data);

        return $this->success('Promoción creada', $promotion);
    }

    public function update(Request $request, Promotion $promotion, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'in:percentage,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'min_subtotal' => ['sometimes', 'numeric', 'min:0'],
            'priority' => ['sometimes', 'integer', 'min:1'],
            'stackable' => ['boolean'],
            'active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $promotion->update($data);
        $auditLogger->log('promotion.updated', $request->user(), Promotion::class, $promotion->id, ['changes' => $data]);

        return $this->success('Promoción actualizada', $promotion);
    }
}

<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\POS\CashSessionService;
use App\Models\CashSession;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class CashSessionController extends BaseApiController
{
    public function index(Request $request)
    {
        $sessions = CashSession::query()
            ->with('warehouse', 'movements')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when(!$request->user()->isAdmin(), fn($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('opened_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($sessions, 'Cajas listadas');
    }

    public function open(Request $request, CashSessionService $cashSessionService, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $session = $cashSessionService->open($request->user(), $data['warehouse_id'], (float) $data['opening_amount']);

        $auditLogger->log('cash_session.opened', $request->user(), CashSession::class, $session->id, [
            'opening_amount' => $session->opening_amount,
        ]);

        return $this->success('Caja abierta', $session);
    }

    public function close(Request $request, CashSession $cashSession, CashSessionService $cashSessionService, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        if (!$request->user()->isAdmin() && $cashSession->user_id !== $request->user()->id) {
            return $this->error('No autorizado', [], 403);
        }

        $session = $cashSessionService->close($cashSession, (float) $data['closing_amount']);

        $auditLogger->log('cash_session.closed', $request->user(), CashSession::class, $session->id, [
            'closing_amount' => $session->closing_amount,
            'difference_amount' => $session->difference_amount,
        ]);

        return $this->success('Caja cerrada', $session);
    }
}

<?php

/**
 * Controller: Reports (API v1).
 *
 * Provides reporting endpoints for sales, inventory and revenue summaries.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for sales reports and summaries.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controller for generating sales reports (daily/weekly/monthly).
 */
/**
 * Report controller.
 *
 * Provides endpoints to retrieve sales and inventory reports.
 *
 * @package   App\Http\Controllers\API\V1
 */
class ReportController extends BaseApiController
{
    public function daily(Request $request)
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $query = $this->baseQuery($request)
            ->whereDate('paid_at', $date->toDateString());

        return $this->success('Reporte diario', [
            'date' => $date->toDateString(),
            'total_gross' => $query->sum('total_gross'),
            'total_net' => $query->sum('total_net'),
            'sales' => $query->count(),
        ]);
    }

    public function weekly(Request $request)
    {
        $weekStart = Carbon::parse($request->input('week', now()->startOfWeek()));
        $current = $this->baseQuery($request)
            ->whereBetween('paid_at', [$weekStart, (clone $weekStart)->endOfWeek()]);
        $previous = $this->baseQuery($request)
            ->whereBetween(
                'paid_at',
                [(clone $weekStart)->subWeek(), (clone $weekStart)->subWeek()->endOfWeek()]
            );

        return $this->success('Reporte semanal', [
            'week' => $weekStart->toDateString(),
            'current' => [
                'total' => $current->sum('total_net'),
                'sales' => $current->count(),
            ],
            'previous' => [
                'total' => $previous->sum('total_net'),
                'sales' => $previous->count(),
            ],
        ]);
    }

    public function monthly(Request $request)
    {
        $monthStart = Carbon::parse($request->input('month', now()->startOfMonth()));
        $current = $this->baseQuery($request)
            ->whereBetween('paid_at', [$monthStart, (clone $monthStart)->endOfMonth()]);
        $previous = $this->baseQuery($request)
            ->whereBetween(
                'paid_at',
                [(clone $monthStart)->subMonth()->startOfMonth(), (clone $monthStart)->subMonth()->endOfMonth()]
            );

        return $this->success('Reporte mensual', [
            'month' => $monthStart->format('Y-m'),
            'current' => [
                'total' => $current->sum('total_net'),
                'sales' => $current->count(),
            ],
            'previous' => [
                'total' => $previous->sum('total_net'),
                'sales' => $previous->count(),
            ],
        ]);
    }

    public function bySeller(Request $request)
    {
        $query = $this->baseQuery($request)
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->selectRaw('users.id as id, users.name as seller_name, SUM(sales.total_net) as total, COUNT(*) as sales')
            ->groupBy('users.id', 'users.name');

        $data = $query->get();

        return $this->success('Reporte por vendedor', $data);
    }

    protected function baseQuery(Request $request)
    {
        return Sale::query()
            ->when(
                $request->filled('warehouse_id'),
                fn($q) => $q->where('warehouse_id', $request->input('warehouse_id'))
            )
            ->when($request->filled('product_type_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($sub) use ($request) {
                    $sub->where('product_type_id', $request->input('product_type_id'));
                });
            });
    }
}

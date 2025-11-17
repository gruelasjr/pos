<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends BaseApiController
{
    public function daily(Request $request)
    {
        $date = Carbon::parse($request->input('fecha', now()->toDateString()));
        $query = $this->baseQuery($request)
            ->whereDate('pagado_en', $date->toDateString());

        return $this->success([
            'fecha' => $date->toDateString(),
            'total_bruto' => $query->sum('total_bruto'),
            'total_neto' => $query->sum('total_neto'),
            'ventas' => $query->count(),
        ]);
    }

    public function weekly(Request $request)
    {
        $weekStart = Carbon::parse($request->input('semana', now()->startOfWeek()));
        $current = $this->baseQuery($request)
            ->whereBetween('pagado_en', [$weekStart, (clone $weekStart)->endOfWeek()]);
        $previous = $this->baseQuery($request)
            ->whereBetween('pagado_en', [(clone $weekStart)->subWeek(), (clone $weekStart)->subWeek()->endOfWeek()]);

        return $this->success([
            'semana' => $weekStart->toDateString(),
            'actual' => [
                'total' => $current->sum('total_neto'),
                'ventas' => $current->count(),
            ],
            'anterior' => [
                'total' => $previous->sum('total_neto'),
                'ventas' => $previous->count(),
            ],
        ]);
    }

    public function monthly(Request $request)
    {
        $monthStart = Carbon::parse($request->input('mes', now()->startOfMonth()));
        $current = $this->baseQuery($request)
            ->whereBetween('pagado_en', [$monthStart, (clone $monthStart)->endOfMonth()]);
        $previous = $this->baseQuery($request)
            ->whereBetween('pagado_en', [(clone $monthStart)->subMonth()->startOfMonth(), (clone $monthStart)->subMonth()->endOfMonth()]);

        return $this->success([
            'mes' => $monthStart->format('Y-m'),
            'actual' => [
                'total' => $current->sum('total_neto'),
                'ventas' => $current->count(),
            ],
            'anterior' => [
                'total' => $previous->sum('total_neto'),
                'ventas' => $previous->count(),
            ],
        ]);
    }

    public function bySeller(Request $request)
    {
        $query = $this->baseQuery($request)
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->selectRaw('users.id as id, users.name as seller_name, SUM(sales.total_neto) as total, COUNT(*) as ventas')
            ->groupBy('users.id', 'users.name');

        $data = $query->get();

        return $this->success($data);
    }

    protected function baseQuery(Request $request)
    {
        return Sale::query()
            ->when($request->filled('almacen_id'), fn ($q) => $q->where('warehouse_id', $request->input('almacen_id')))
            ->when($request->filled('tipo_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($sub) use ($request) {
                    $sub->where('product_type_id', $request->input('tipo_id'));
                });
            });
    }
}

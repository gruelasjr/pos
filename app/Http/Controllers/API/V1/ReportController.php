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

use App\Services\Reports\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Models\User;
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
    public function overview(Request $request, ReportDataService $reports)
    {
        return $this->success('Resumen de reportes', $reports->overview($this->filters($request)));
    }

    public function bestSellers(Request $request, ReportDataService $reports)
    {
        return $this->success('Productos más vendidos', $reports->bestSellers($this->filters($request)));
    }

    public function export(Request $request, ReportDataService $reports)
    {
        $data = $request->validate([
            'report' => ['required', 'in:overview,best-sellers'],
            'format' => ['required', 'in:csv,pdf'],
        ]);
        $filters = $this->filters($request);
        $payload = $data['report'] === 'overview' ? $reports->overview($filters) : $reports->bestSellers($filters);
        $filename = 'pos-faro-' . $data['report'] . '-' . $payload['period']['from'] . '-' . $payload['period']['to'];

        if ($data['format'] === 'csv') {
            return $this->csvResponse($filename . '.csv', $this->exportRows($data['report'], $payload));
        }

        return Pdf::loadView('reports.pdf', ['report' => $data['report'], 'payload' => $payload])
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->download($filename . '.pdf');
    }

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
        $user = new User();
        $userTable = $user->getTable();
        $userKey = $user->getKeyName();

        $query = $this->baseQuery($request)
            ->join($userTable, 'sales.user_id', '=', $userTable . '.' . $userKey)
            ->selectRaw(
                $userTable . '.' . $userKey
                . ' as id, ' . $userTable
                . '.name as seller_name, SUM(sales.total_net) as total, COUNT(*) as sales'
            )
            ->groupBy($userTable . '.' . $userKey, $userTable . '.name');

        $data = $query->get();

        return $this->success('Reporte por vendedor', $data);
    }

    public function dailyExport(Request $request)
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()))->toDateString();
        $query = $this->baseQuery($request)
            ->whereDate('paid_at', $date);

        $rows = [[
            'date',
            'total_gross',
            'total_net',
            'sales_count',
        ], [
            $date,
            (string) $query->sum('total_gross'),
            (string) $query->sum('total_net'),
            (string) $query->count(),
        ]];

        return $this->csvResponse('daily_report_' . $date . '.csv', $rows);
    }

    public function bySellerExport(Request $request)
    {
        $user = new User();
        $userTable = $user->getTable();
        $userKey = $user->getKeyName();

        $data = $this->baseQuery($request)
            ->join($userTable, 'sales.user_id', '=', $userTable . '.' . $userKey)
            ->selectRaw(
                $userTable . '.' . $userKey
                . ' as id, ' . $userTable
                . '.name as seller_name, SUM(sales.total_net) as total, COUNT(*) as sales'
            )
            ->groupBy($userTable . '.' . $userKey, $userTable . '.name')
            ->get();

        $rows = [['seller_id', 'seller_name', 'sales_count', 'total_net']];
        foreach ($data as $row) {
            $rows[] = [(string) $row->id, (string) $row->seller_name, (string) $row->sales, (string) $row->total];
        }

        return $this->csvResponse('by_seller_report.csv', $rows);
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

    protected function csvResponse(string $filename, array $rows)
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);

                return '"' . $escaped . '"';
            }, $row)) . "\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'group_by' => ['nullable', 'array', 'max:3'],
            'group_by.*' => ['string', 'max:80'],
        ]);

        return $request->only('from', 'to', 'warehouse_id', 'group_by');
    }

    private function exportRows(string $report, array $payload): array
    {
        if ($report === 'overview') {
            $rows = [['date', 'net_sales', 'tickets']];
            foreach ($payload['series']['current'] as $point) {
                $rows[] = [$point['date'], $point['total'], $point['tickets']];
            }
            $rows[] = [];
            $rows[] = ['seller', 'net_sales', 'tickets', 'average_ticket', 'delta_percent'];
            foreach ($payload['sellers'] as $seller) {
                $rows[] = [
                    $seller['seller_name'],
                    $seller['total'],
                    $seller['sales'],
                    $seller['average_ticket'],
                    $seller['delta'],
                ];
            }

            return $rows;
        }

        $rows = [['path', 'product', 'sku', 'units', 'net_sales', 'tickets', 'stock']];
        $this->flattenBestSellerRows($payload['tree'], [], $rows);

        return $rows;
    }

    private function flattenBestSellerRows(array $nodes, array $path, array &$rows): void
    {
        foreach ($nodes as $node) {
            if ($node['type'] === 'group') {
                $this->flattenBestSellerRows($node['children'], [...$path, $node['label']], $rows);
                continue;
            }
            $rows[] = [
                implode(' > ', $path),
                $node['name'],
                $node['sku'],
                $node['units'],
                $node['net_sales'],
                $node['tickets'],
                $node['stock'],
            ];
        }
    }
}

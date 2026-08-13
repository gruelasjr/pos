<?php

namespace App\Services\Reports;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductMetadataDefinition;
use App\Models\ProductMetadataValue;
use App\Models\ProductTag;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReportDataService
{
    public function overview(array $filters): array
    {
        [$from, $to] = $this->period($filters, 6);
        $days = $from->diffInDays($to) + 1;
        $previousTo = (clone $from)->subDay()->endOfDay();
        $previousFrom = (clone $previousTo)->subDays($days - 1)->startOfDay();

        $current = $this->salesQuery($filters)->whereBetween('paid_at', [$from, $to]);
        $previous = $this->salesQuery($filters)->whereBetween('paid_at', [$previousFrom, $previousTo]);
        $summary = $this->summary(clone $current);
        $previousSummary = $this->summary(clone $previous);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'previous_period' => ['from' => $previousFrom->toDateString(), 'to' => $previousTo->toDateString()],
            'summary' => $this->withDeltas($summary, $previousSummary),
            'series' => [
                'current' => $this->series(clone $current, $from, $to),
                'previous' => $this->series(clone $previous, $previousFrom, $previousTo),
            ],
            'sellers' => $this->sellerRanking($filters, $from, $to, $previousFrom, $previousTo),
            'updated_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ];
    }

    public function bestSellers(array $filters): array
    {
        [$from, $to] = $this->period($filters, 29);
        $groupBy = array_values($filters['group_by'] ?? ['category']);
        $this->validateGrouping($groupBy);

        $aggregates = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.paid_at', [$from, $to])
            ->when(
                $filters['warehouse_id'] ?? null,
                fn ($query, $warehouse) => $query->where('sales.warehouse_id', $warehouse)
            )
            ->selectRaw(
                'sale_items.product_id, SUM(sale_items.quantity) as units, '
                . 'SUM(sale_items.subtotal) as net_sales, COUNT(DISTINCT sale_items.sale_id) as tickets'
            )
            ->groupBy('sale_items.product_id')
            ->orderByDesc('units')
            ->get();

        $products = Product::query()
            ->with('type', 'tags', 'metadataValues.definition')
            ->whereIn('id', $aggregates->pluck('product_id'))
            ->get()
            ->keyBy('id');
        $stocks = Inventory::query()
            ->whereIn('product_id', $aggregates->pluck('product_id'))
            ->when(
                $filters['warehouse_id'] ?? null,
                fn ($query, $warehouse) => $query->where('warehouse_id', $warehouse)
            )
            ->selectRaw('product_id, SUM(stock) as stock')
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $rows = $aggregates->map(function ($aggregate) use ($products, $stocks) {
            $product = $products->get($aggregate->product_id);
            if (! $product) {
                return null;
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->short_description,
                'photo_url' => $product->photo_url,
                'sale_price' => (float) $product->sale_price,
                'units' => (int) $aggregate->units,
                'net_sales' => (float) $aggregate->net_sales,
                'tickets' => (int) $aggregate->tickets,
                'stock' => (int) ($stocks[$product->id] ?? 0),
                'category' => ['key' => $product->type->id, 'label' => $product->type->name],
                'tags' => $product->tags
                    ->map(fn (ProductTag $tag) => ['key' => $tag->id, 'label' => $tag->name])
                    ->values()->all(),
                'metadata' => $product->metadataValues->mapWithKeys(fn (ProductMetadataValue $value) => [
                    $value->definition->key => [
                        'key' => (string) $value->resolvedValue(),
                        'label' => $this->formatMetadata($value->resolvedValue()),
                    ],
                ])->all(),
            ];
        })->filter()->values();

        $summary = [
            'units' => (int) $rows->sum('units'),
            'net_sales' => round((float) $rows->sum('net_sales'), 2),
            'tickets' => (int) SaleItem::query()->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.paid_at', [$from, $to])
                ->when(
                    $filters['warehouse_id'] ?? null,
                    fn ($query, $warehouse) => $query->where('sales.warehouse_id', $warehouse)
                )
                ->distinct('sale_items.sale_id')->count('sale_items.sale_id'),
            'products' => $rows->count(),
            'stock' => (int) $rows->sum('stock'),
        ];

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => $summary,
            'group_by' => $groupBy,
            'non_additive_tag_totals' => in_array('tag', $groupBy, true),
            'tree' => $this->groupRows($rows, $groupBy),
            'updated_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ];
    }

    private function period(array $filters, int $defaultLookback): array
    {
        $to = Carbon::parse($filters['to'] ?? now())->endOfDay();
        $from = Carbon::parse($filters['from'] ?? (clone $to)->subDays($defaultLookback))->startOfDay();
        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages(['from' => ['La fecha inicial debe ser anterior a la final.']]);
        }

        return [$from, $to];
    }

    private function salesQuery(array $filters): Builder
    {
        return Sale::query()->when(
            $filters['warehouse_id'] ?? null,
            fn ($query, $warehouse) => $query->where('warehouse_id', $warehouse)
        );
    }

    private function summary(Builder $query): array
    {
        $total = (float) (clone $query)->sum('total_net');
        $tickets = (int) (clone $query)->count();

        return [
            'net_sales' => $total,
            'tickets' => $tickets,
            'average_ticket' => $tickets ? round($total / $tickets, 2) : 0.0,
        ];
    }

    private function withDeltas(array $current, array $previous): array
    {
        foreach ($current as $key => $value) {
            $baseline = (float) ($previous[$key] ?? 0);
            $current[$key . '_delta'] = $baseline === 0.0
                ? ($value > 0 ? 100.0 : 0.0)
                : round((($value - $baseline) / $baseline) * 100, 1);
        }

        return $current;
    }

    private function series(Builder $query, Carbon $from, Carbon $to): array
    {
        $values = $query->selectRaw('DATE(paid_at) as report_date, SUM(total_net) as total, COUNT(*) as tickets')
            ->groupBy('report_date')->orderBy('report_date')->get()->keyBy('report_date');

        return collect(range(0, $from->diffInDays($to)))->map(function ($offset) use ($from, $values) {
            $date = (clone $from)->addDays($offset)->toDateString();
            if (! $values->has($date)) {
                return ['date' => $date, 'total' => 0.0, 'tickets' => 0];
            }
            $row = $values->get($date);

            return [
                'date' => $date,
                'total' => (float) $row->getAttribute('total'),
                'tickets' => (int) $row->getAttribute('tickets'),
            ];
        })->all();
    }

    private function sellerRanking(
        array $filters,
        Carbon $from,
        Carbon $to,
        Carbon $previousFrom,
        Carbon $previousTo
    ): array {
        $current = $this->sellerRows($filters, $from, $to);
        $previous = $this->sellerRows($filters, $previousFrom, $previousTo)->keyBy('id');

        return $current->map(function ($row) use ($previous) {
            $prior = $previous->has($row->id) ? (float) $previous->get($row->id)->total : 0.0;
            return [
                'id' => $row->id,
                'seller_name' => $row->seller_name,
                'total' => (float) $row->total,
                'sales' => (int) $row->sales,
                'average_ticket' => $row->sales ? round((float) $row->total / (int) $row->sales, 2) : 0,
                'delta' => $prior === 0.0
                    ? ((float) $row->total > 0 ? 100.0 : 0.0)
                    : round((((float) $row->total - $prior) / $prior) * 100, 1),
            ];
        })->all();
    }

    private function sellerRows(array $filters, Carbon $from, Carbon $to): Collection
    {
        $user = new User();
        $table = $user->getTable();
        $key = $user->getKeyName();

        return $this->salesQuery($filters)->join($table, 'sales.user_id', '=', "{$table}.{$key}")
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw(
                "{$table}.{$key} as id, {$table}.name as seller_name, "
                . 'SUM(sales.total_net) as total, COUNT(*) as sales'
            )
            ->groupBy("{$table}.{$key}", "{$table}.name")->orderByDesc('total')->get();
    }

    private function validateGrouping(array $groupBy): void
    {
        if (count($groupBy) > 3 || count($groupBy) !== count(array_unique($groupBy))) {
            throw ValidationException::withMessages(['group_by' => ['Usa hasta tres dimensiones diferentes.']]);
        }
        $metadataKeys = ProductMetadataDefinition::query()->where('active', true)->pluck('key')->all();
        foreach ($groupBy as $dimension) {
            if (in_array($dimension, ['category', 'tag'], true)) {
                continue;
            }
            if (! str_starts_with($dimension, 'metadata:') || ! in_array(substr($dimension, 9), $metadataKeys, true)) {
                throw ValidationException::withMessages(['group_by' => ["Dimensión no válida: {$dimension}."]]);
            }
        }
    }

    private function groupRows(Collection $rows, array $dimensions, int $level = 0): array
    {
        if ($level >= count($dimensions)) {
            return $rows->sortByDesc('units')->values()->map(function ($row, $index) {
                return ['type' => 'product', 'rank' => $index + 1] + $row;
            })->all();
        }

        $dimension = $dimensions[$level];
        $groups = [];
        foreach ($rows as $row) {
            $values = $this->dimensionValues($row, $dimension);
            foreach ($values as $value) {
                $id = $dimension . ':' . $value['key'];
                $groups[$id] ??= ['key' => $id, 'label' => $value['label'], 'rows' => collect()];
                $groups[$id]['rows']->push($row);
            }
        }

        return collect($groups)->map(function ($group) use ($dimensions, $level, $dimension) {
            $rows = $group['rows'];
            return [
                'type' => 'group',
                'key' => $group['key'],
                'label' => $group['label'],
                'dimension' => $dimension,
                'units' => (int) $rows->sum('units'),
                'net_sales' => round((float) $rows->sum('net_sales'), 2),
                'tickets' => (int) $rows->sum('tickets'),
                'stock' => (int) $rows->sum('stock'),
                'children' => $this->groupRows($rows, $dimensions, $level + 1),
            ];
        })->sortByDesc('units')->values()->all();
    }

    private function dimensionValues(array $row, string $dimension): array
    {
        if ($dimension === 'category') {
            return [$row['category']];
        }
        if ($dimension === 'tag') {
            return $row['tags'] ?: [['key' => 'untagged', 'label' => 'Sin tag']];
        }
        $key = substr($dimension, 9);

        return [$row['metadata'][$key] ?? ['key' => 'unset', 'label' => 'Sin dato']];
    }

    private function formatMetadata(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        return $value === null || $value === '' ? 'Sin dato' : (string) $value;
    }
}

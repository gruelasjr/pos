<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\ReservedSkuRange;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RuntimeException;

class SkuGeneratorService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function reserve(int $cantidad, ?string $prefijo = null): array
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('cantidad_invalida');
        }

        return $this->db->transaction(function () use ($cantidad, $prefijo) {
            $rangeQuery = ReservedSkuRange::query()
                ->when($prefijo, fn ($q) => $q->where('prefijo', $prefijo))
                ->where(function ($q) {
                    $q->whereNull('usado_hasta')
                        ->orWhereColumn('usado_hasta', '<', 'hasta');
                })
                ->orderBy('updated_at');

            /** @var ReservedSkuRange|null $range */
            $range = $rangeQuery->lockForUpdate()->first();

            if (!$range) {
                throw new RuntimeException('sin_rangos_disponibles');
            }

            $skus = $this->buildSkus($range, $cantidad);
            $range->usado_hasta = $this->maxNumeric($skus);
            $range->save();

            return [
                'rango_id' => $range->id,
                'skus' => $skus,
            ];
        });
    }

    protected function buildSkus(ReservedSkuRange $range, int $cantidad): array
    {
        $skus = [];
        $prefix = $range->prefijo ?? '';
        $current = $range->usado_hasta ? $range->usado_hasta + 1 : $range->desde;

        while (count($skus) < $cantidad) {
            if ($current > $range->hasta) {
                throw new RuntimeException('rango_agotado');
            }

            $candidateNumber = $current;
            $candidateSku = $prefix . str_pad((string) $candidateNumber, 6, '0', STR_PAD_LEFT);

            if (!Product::query()->where('sku', $candidateSku)->exists()) {
                $skus[] = $candidateSku;
            }

            $current++;
        }

        return $skus;
    }

    protected function maxNumeric(array $skus): int
    {
        return Collection::make($skus)
            ->map(fn ($sku) => (int) preg_replace('/[^0-9]/', '', $sku))
            ->max();
    }
}

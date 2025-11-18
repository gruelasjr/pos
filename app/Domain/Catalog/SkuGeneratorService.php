<?php

/**
 * Provides SKU range reservation and generation for products.
 *
 * PHP 8.1+
 *
 * @package   App\Domain\Catalog
 */

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\ReservedSkuRange;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Coordinates SKU range reservation and generation for products.
 *
 * Ensures atomicity and uniqueness of SKUs using database transactions and range locking.
 */
class SkuGeneratorService
{
    /**
     * Creates a SKU generator bound to a database manager.
     *
     * @param DatabaseManager $db  Database manager instance.
     */
    public function __construct(private DatabaseManager $db)
    {
        // No body
    }

    /**
     * Reserves a range of SKUs, optionally filtered by prefix.
     *
     * Throws when no ranges are available or input is invalid.
     *
     * @param  int         $quantity  Number of SKUs to reserve (must be > 0).
     * @param  string|null $prefix    Optional prefix to filter ranges.
     * @return array{range_id: string, skus: string[]}  Reserved range ID and SKUs.
     * @throws RuntimeException       When no ranges are available or input is invalid.
     */
    public function reserve(int $quantity, ?string $prefix = null): array
    {
        if ($quantity <= 0) {
            throw new RuntimeException('cantidad_invalida');
        }

        return $this->db->transaction(function () use ($quantity, $prefix) {
            $rangeQuery = ReservedSkuRange::query()
                ->when($prefix, fn($q) => $q->where('prefix', $prefix))
                ->where(function ($q) {
                    $q->whereNull('used_up_to')
                        ->orWhereColumn('used_up_to', '<', 'to');
                })
                ->orderBy('updated_at');

            /** @var ReservedSkuRange|null $range */
            $range = $rangeQuery->lockForUpdate()->first();

            if (!$range) {
                throw new RuntimeException('sin_rangos_disponibles');
            }

            $skus = $this->buildSkus($range, $quantity);
            $range->used_up_to = $this->maxNumeric($skus);
            $range->save();

            return [
                'range_id' => $range->id,
                'skus' => $skus,
            ];
        });
    }

    /**
     * Builds a list of unique SKUs for a given range and quantity.
     *
     * Throws when the range is exhausted.
     *
     * @param  ReservedSkuRange $range     Range to use for SKUs.
     * @param  int              $quantity  Number of SKUs to build.
     * @return string[]                   List of generated SKUs.
     * @throws RuntimeException           When the range is exhausted.
     */
    protected function buildSkus(ReservedSkuRange $range, int $quantity): array
    {
        $skus = [];
        $prefix = $range->prefix ?? '';
        $current = $range->used_up_to ? $range->used_up_to + 1 : $range->from;

        while (count($skus) < $quantity) {
            if ($current > $range->to) {
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

    /**
     * Returns the maximum numeric value found in a list of SKUs.
     *
     * @param  string[] $skus  List of SKUs.
     * @return int             Maximum numeric value.
     */
    protected function maxNumeric(array $skus): int
    {
        return Collection::make($skus)
            ->map(fn($sku) => (int) preg_replace('/[^0-9]/', '', $sku))
            ->max();
    }
}

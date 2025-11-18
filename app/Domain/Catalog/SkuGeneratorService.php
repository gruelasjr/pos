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
     * @param  int         $cantidad  Number of SKUs to reserve (must be > 0).
     * @param  string|null $prefijo   Optional prefix to filter ranges.
     * @return array{rango_id: int, skus: string[]}  Reserved range ID and SKUs.
     * @throws RuntimeException       When no ranges are available or input is invalid.
     */
    public function reserve(int $cantidad, ?string $prefijo = null): array
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('cantidad_invalida');
        }

        return $this->db->transaction(function () use ($cantidad, $prefijo) {
            $rangeQuery = ReservedSkuRange::query()
                ->when($prefijo, fn($q) => $q->where('prefijo', $prefijo))
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

    /**
     * Builds a list of unique SKUs for a given range and quantity.
     *
     * Throws when the range is exhausted.
     *
     * @param  ReservedSkuRange $range     Range to use for SKUs.
     * @param  int              $cantidad  Number of SKUs to build.
     * @return string[]                   List of generated SKUs.
     * @throws RuntimeException           When the range is exhausted.
     */
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

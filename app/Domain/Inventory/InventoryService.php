<?php

/**
 * Inventory domain service.
 *
 * Responsible for inventory adjustments and validations.
 *
 * PHP 8.1+
 *
 * @package   App\Domain\Inventory
 */

namespace App\Domain\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

/**
 * Service to mutate and query inventory state.
 */
/**
 * Transactional guarantees:
 *
 * This service performs inventory assertions and adjustments inside
 * database transactions using the injected `DatabaseManager`. The
 * implementation relies on row-level locks (`FOR UPDATE`) and therefore
 * requires that inventory state and related product tables live in the
 * same database connection. Cross-database adjustments are not supported
 * and may lead to race conditions.
 */
class InventoryService
{
    public function __construct(private DatabaseManager $db)
    {
        // No body
    }

    /**
     * Adjust inventory for a product in a warehouse.
     *
     * @param  string      $productId
     * @param  string      $warehouseId
     * @param  int         $delta       Positive to add, negative to remove.
     * @param  string|null $reason
     * @return Inventory
     */
    public function adjust(string $productId, string $warehouseId, int $delta, ?string $reason = null): Inventory
    {
        if ($delta === 0) {
            throw new UnprocessableEntityException('delta_invalido');
        }

        $product = Product::findOrFail($productId);

        return $this->db->transaction(function () use ($product, $warehouseId, $delta) {
            /** @var Inventory $inventory */
            $inventory = Inventory::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = new Inventory([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'stock' => 0,
                    'reorder_point' => 0,
                ]);
            }

            $futureStock = $inventory->stock + $delta;
            if ($futureStock < 0) {
                throw new UnprocessableEntityException('inventario_insuficiente');
            }

            $inventory->stock = $futureStock;
            $inventory->save();

            $this->refreshProductStock($product);

            return $inventory->refresh();
        });
    }

    /**
     * Ensure a product has sufficient stock in the given warehouse.
     *
     * @param  Product   $product
     * @param  Warehouse $warehouse
     * @param  int       $quantity
     * @return void
     * @throws \Equidna\Toolkit\Exceptions\UnprocessableEntityException When inventory is insufficient.
     */
    public function assertSufficient(Product $product, Warehouse $warehouse, int $quantity): void
    {
        /** @var Inventory|null $inventory */
        $inventory = $product->inventories()
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->stock < $quantity) {
            throw new UnprocessableEntityException('inventario_insuficiente');
        }
    }

    /**
     * Recalculate and persist product aggregate stock state.
     *
     * @param  Product $product
     * @return void
     */
    protected function refreshProductStock(Product $product): void
    {
        $totalStock = $product->inventories()->sum('stock');

        if ($totalStock === 0 && !$product->stock_end_date) {
            $product->stock_end_date = now();
            $product->save();
        } elseif ($totalStock > 0 && $product->stock_end_date) {
            $product->stock_end_date = null;
            $product->save();
        }
    }
}

<?php

namespace App\Domain\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class InventoryService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function adjust(string $productId, string $warehouseId, int $delta, ?string $reason = null): Inventory
    {
        if ($delta === 0) {
            throw new RuntimeException('delta_invalido');
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
                throw new RuntimeException('inventario_insuficiente');
            }

            $inventory->stock = $futureStock;
            $inventory->save();

            $this->refreshProductStock($product);

            return $inventory->refresh();
        });
    }

    public function assertSufficient(Product $product, Warehouse $warehouse, int $quantity): void
    {
        /** @var Inventory|null $inventory */
        $inventory = $product->inventories()
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->stock < $quantity) {
            throw new RuntimeException('inventario_insuficiente');
        }
    }

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

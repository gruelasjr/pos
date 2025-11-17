<?php

namespace App\Domain\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

class InventoryService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function adjust(string $productId, string $warehouseId, int $delta, ?string $motivo = null): Inventory
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
                    'existencias' => 0,
                    'punto_reorden' => 0,
                ]);
            }

            $futureStock = $inventory->existencias + $delta;
            if ($futureStock < 0) {
                throw new RuntimeException('inventario_insuficiente');
            }

            $inventory->existencias = $futureStock;
            $inventory->save();

            $this->refreshProductStock($product);

            return $inventory->refresh();
        });
    }

    public function assertSufficient(Product $product, Warehouse $warehouse, int $cantidad): void
    {
        /** @var Inventory|null $inventory */
        $inventory = $product->inventories()
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->existencias < $cantidad) {
            throw new RuntimeException('inventario_insuficiente');
        }
    }

    protected function refreshProductStock(Product $product): void
    {
        $totalStock = $product->inventories()->sum('existencias');

        if ($totalStock === 0 && !$product->fecha_fin_stock) {
            $product->fecha_fin_stock = now();
            $product->save();
        } elseif ($totalStock > 0 && $product->fecha_fin_stock) {
            $product->fecha_fin_stock = null;
            $product->save();
        }
    }
}

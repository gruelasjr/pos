<?php

namespace App\Domain\Sales;

use App\Domain\POS\CashSessionService;
use App\Domain\Shared\OutboxPublisher;
use App\Domain\Inventory\InventoryService;
use App\Models\ReturnItem;
use App\Models\ReturnNote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Equidna\Toolkit\Exceptions\UnprocessableEntityException;

class ReturnService
{
    public function __construct(
        private DatabaseManager $db,
        private InventoryService $inventoryService,
        private CashSessionService $cashSessionService,
        private OutboxPublisher $outboxPublisher
    ) {
    }

    public function createReturn(Sale $sale, User $user, array $items, ?string $reason = null): ReturnNote
    {
        if (empty($items)) {
            throw new UnprocessableEntityException('devolucion_sin_items');
        }

        return $this->db->transaction(function () use ($sale, $user, $items, $reason) {
            $sale->load('items');

            $note = ReturnNote::create([
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'status' => 'approved',
                'total_refund' => 0,
            ]);

            $total = 0.0;

            foreach ($items as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = $sale->items()->where('id', $row['sale_item_id'])->firstOrFail();
                $quantity = (int) $row['quantity'];

                $alreadyReturned = ReturnItem::query()
                    ->where('sale_item_id', $saleItem->id)
                    ->sum('quantity');

                $remaining = $saleItem->quantity - (int) $alreadyReturned;
                if ($quantity <= 0 || $quantity > $remaining) {
                    throw new UnprocessableEntityException('cantidad_devolucion_invalida');
                }

                $subtotal = round((float) $saleItem->unit_price * $quantity, 2);

                ReturnItem::create([
                    'return_note_id' => $note->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $saleItem->unit_price,
                    'subtotal' => $subtotal,
                ]);

                $this->inventoryService->adjust($saleItem->product_id, $sale->warehouse_id, $quantity, 'return');
                $total += $subtotal;
            }

            $note->total_refund = $total;
            $note->save();

            if ($sale->cash_session_id) {
                $this->cashSessionService->registerMovement(
                    $sale->cash_session_id,
                    'refund',
                    -1 * $total,
                    ReturnNote::class,
                    $note->id,
                    $reason
                );
            }

            $this->outboxPublisher->publish('sale.return_created', [
                'return_note_id' => $note->id,
                'sale_id' => $sale->id,
                'total_refund' => $note->total_refund,
            ], ReturnNote::class, $note->id);

            return $note->load('items', 'sale');
        });
    }
}

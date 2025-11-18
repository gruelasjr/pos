<?php

/**
 * Receipt rendering utilities.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

namespace App\Support;

use App\Models\Sale;

class ReceiptRenderer
{
    public function html(Sale $sale): string
    {
        $rows = $sale->items->map(function ($item) {
            return sprintf(
                '<tr><td>%s</td><td>%d</td><td>$%0.2f</td><td>$%0.2f</td></tr>',
                e($item->description),
                $item->quantity,
                $item->unit_price,
                $item->subtotal
            );
        })->join('');

        return <<<HTML
            <h1>Recibo {$sale->folio}</h1>
            <p>Almacén: {$sale->warehouse->name}</p>
            <p>Vendedor: {$sale->seller->name}</p>
            <table>{$rows}</table>
            <p>Total: {$sale->total_net}</p>
        HTML;
    }
}

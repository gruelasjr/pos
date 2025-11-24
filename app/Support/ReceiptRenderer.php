<?php

/**
 * Helper: render receipts to HTML for printing or emailing.
 *
 * Centralizes receipt markup rendering logic used by the POS and notifications.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

/**
 * Receipt rendering utilities.
 *
 * PHP 8.1+
 *
 * @package   App\Support
 */

namespace App\Support;

use App\Models\Sale;

/**
 * Helper to render receipts as HTML.
 *
 * Produces printable and email-ready HTML for sale receipts.
 *
 * @package   App\Support
 */
class ReceiptRenderer
{
    public function html(Sale $sale): string
    {
        // Render the receipt using a Blade view to ensure proper escaping
        return view('emails.receipt', ['sale' => $sale])->render();
    }
}

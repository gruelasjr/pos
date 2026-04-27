<?php

namespace App\Services\Integrations\Mocks;

use App\Services\Integrations\ReceiptPrinter;
use Illuminate\Support\Str;

class MockReceiptPrinter implements ReceiptPrinter
{
    public function print(array $payload): array
    {
        if ((bool) config('pos_integrations.receipt_printer.mock_fail', false)) {
            return [
                'ok' => false,
                'provider' => 'mock-receipt-printer',
                'status' => 'failed',
                'error_code' => 'mock_printer_failed',
            ];
        }

        return [
            'ok' => true,
            'provider' => 'mock-receipt-printer',
            'status' => 'printed',
            'job_id' => 'print_' . Str::uuid(),
            'folio' => $payload['folio'] ?? null,
        ];
    }
}

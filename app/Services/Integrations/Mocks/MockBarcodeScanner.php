<?php

namespace App\Services\Integrations\Mocks;

use App\Services\Integrations\BarcodeScanner;

class MockBarcodeScanner implements BarcodeScanner
{
    public function parse(string $input): array
    {
        $normalized = strtoupper(trim($input));
        $quantity = 1;
        $sku = $normalized;

        if (preg_match('/^(?<quantity>[1-9][0-9]*)\*(?<sku>[A-Z0-9._-]+)$/', $normalized, $matches)) {
            $quantity = (int) $matches['quantity'];
            $sku = $matches['sku'];
        }

        return [
            'ok' => $sku !== '',
            'provider' => 'mock-barcode-scanner',
            'raw' => $input,
            'sku' => $sku,
            'quantity' => $quantity,
        ];
    }
}

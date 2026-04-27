<?php

namespace App\Services\Integrations\Mocks;

use App\Services\Integrations\FiscalProvider;
use Illuminate\Support\Str;

class MockFiscalProvider implements FiscalProvider
{
    public function issueInvoice(array $payload): array
    {
        if ((bool) config('pos_integrations.fiscal.mock_fail', false)) {
            return [
                'ok' => false,
                'provider' => 'mock-fiscal',
                'status' => 'failed',
                'error_code' => 'mock_fiscal_failed',
            ];
        }

        return [
            'ok' => true,
            'provider' => 'mock-fiscal',
            'status' => 'issued',
            'reference' => 'fis_' . Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'folio' => $payload['folio'] ?? null,
        ];
    }
}

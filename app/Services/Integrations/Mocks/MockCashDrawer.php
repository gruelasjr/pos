<?php

namespace App\Services\Integrations\Mocks;

use App\Services\Integrations\CashDrawer;
use Illuminate\Support\Str;

class MockCashDrawer implements CashDrawer
{
    public function open(array $payload): array
    {
        if ((bool) config('pos_integrations.cash_drawer.mock_fail', false)) {
            return [
                'ok' => false,
                'provider' => 'mock-cash-drawer',
                'status' => 'failed',
                'error_code' => 'mock_drawer_failed',
            ];
        }

        return [
            'ok' => true,
            'provider' => 'mock-cash-drawer',
            'status' => 'opened',
            'pulse_id' => 'drawer_' . Str::uuid(),
            'folio' => $payload['folio'] ?? null,
        ];
    }
}

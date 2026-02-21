<?php

namespace App\Services\Integrations\Stubs;

use App\Services\Integrations\ERPConnector;

class StubERPConnector implements ERPConnector
{
    public function syncSale(array $payload): array
    {
        return [
            'ok' => true,
            'provider' => 'stub-erp',
            'entity' => 'sale',
            'payload' => $payload,
        ];
    }

    public function syncInventory(array $payload): array
    {
        return [
            'ok' => true,
            'provider' => 'stub-erp',
            'entity' => 'inventory',
            'payload' => $payload,
        ];
    }
}

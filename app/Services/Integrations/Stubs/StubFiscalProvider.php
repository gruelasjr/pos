<?php

namespace App\Services\Integrations\Stubs;

use App\Services\Integrations\FiscalProvider;

class StubFiscalProvider implements FiscalProvider
{
    public function issueInvoice(array $payload): array
    {
        return [
            'ok' => true,
            'provider' => 'stub-fiscal',
            'invoice_id' => uniqid('inv_', true),
            'payload' => $payload,
        ];
    }
}

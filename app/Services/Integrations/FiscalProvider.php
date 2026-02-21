<?php

namespace App\Services\Integrations;

interface FiscalProvider
{
    public function issueInvoice(array $payload): array;
}

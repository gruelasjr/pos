<?php

namespace App\Services\Integrations;

interface ReceiptPrinter
{
    public function print(array $payload): array;
}

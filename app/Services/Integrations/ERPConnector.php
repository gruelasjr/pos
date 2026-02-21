<?php

namespace App\Services\Integrations;

interface ERPConnector
{
    public function syncSale(array $payload): array;

    public function syncInventory(array $payload): array;
}

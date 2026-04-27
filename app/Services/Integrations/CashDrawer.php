<?php

namespace App\Services\Integrations;

interface CashDrawer
{
    public function open(array $payload): array;
}

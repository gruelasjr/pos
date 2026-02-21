<?php

namespace App\Services\Integrations;

interface PaymentGateway
{
    public function charge(array $payload): array;
}

<?php

namespace App\Services\Integrations;

interface PaymentGateway
{
    public function charge(array $payload): array;

    /** Query a previously submitted charge without creating a new charge. */
    public function status(string $providerReference, array $context = []): array;
}

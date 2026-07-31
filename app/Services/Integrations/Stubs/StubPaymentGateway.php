<?php

namespace App\Services\Integrations\Stubs;

use App\Services\Integrations\PaymentGateway;

class StubPaymentGateway implements PaymentGateway
{
    public function charge(array $payload): array
    {
        return [
            'ok' => true,
            'provider' => 'stub-payment',
            'transaction_id' => uniqid('pay_', true),
            'payload' => $payload,
        ];
    }

    public function status(string $providerReference, array $context = []): array
    {
        return ['ok' => false, 'provider' => 'stub-payment', 'status' => 'unknown', 'reference' => $providerReference];
    }
}

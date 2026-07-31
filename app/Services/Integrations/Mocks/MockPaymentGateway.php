<?php

namespace App\Services\Integrations\Mocks;

use App\Services\Integrations\PaymentGateway;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGateway
{
    public function charge(array $payload): array
    {
        $details = $payload['payment_details'] ?? [];
        $declined = (bool) config('pos_integrations.payments.mock_fail', false)
            || (bool) ($details['mock_decline'] ?? false);

        if ($declined) {
            return [
                'ok' => false,
                'provider' => 'mock-payment',
                'status' => 'declined',
                'error_code' => 'mock_declined',
                'message' => 'Mock payment declined by configuration.',
            ];
        }

        return [
            'ok' => true,
            'provider' => 'mock-payment',
            'status' => 'approved',
            'reference' => 'pay_' . Str::uuid(),
            'authorization_code' => strtoupper(Str::random(8)),
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? 'MXN',
            'method' => $payload['method'] ?? null,
        ];
    }

    public function status(string $providerReference, array $context = []): array
    {
        return ['ok' => true, 'provider' => 'mock-payment', 'status' => 'approved', 'reference' => $providerReference];
    }
}

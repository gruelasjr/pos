<?php

namespace App\Services\Integrations\Exceptions;

use RuntimeException;

class PaymentDeclinedException extends RuntimeException
{
    public function __construct(
        private array $requestPayload,
        private array $responsePayload,
        private string $provider
    ) {
        parent::__construct('pago_rechazado');
    }

    public function requestPayload(): array
    {
        return $this->requestPayload;
    }

    public function responsePayload(): array
    {
        return $this->responsePayload;
    }

    public function provider(): string
    {
        return $this->provider;
    }
}

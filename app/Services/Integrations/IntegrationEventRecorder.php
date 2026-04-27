<?php

namespace App\Services\Integrations;

use App\Models\PosIntegrationEvent;
use App\Models\Sale;
use Throwable;

class IntegrationEventRecorder
{
    public function success(
        ?Sale $sale,
        string $operation,
        string $provider,
        array $requestPayload,
        array $responsePayload
    ): PosIntegrationEvent {
        return $this->record($sale, $operation, $provider, 'success', $requestPayload, $responsePayload);
    }

    public function failure(
        ?Sale $sale,
        string $operation,
        string $provider,
        array $requestPayload,
        Throwable|string $error,
        ?array $responsePayload = null
    ): PosIntegrationEvent {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;
        $code = $error instanceof Throwable ? (string) $error->getCode() : null;

        return $this->record(
            $sale,
            $operation,
            $provider,
            'failed',
            $requestPayload,
            $responsePayload,
            $code,
            $message
        );
    }

    public function record(
        ?Sale $sale,
        string $operation,
        string $provider,
        string $status,
        array $requestPayload,
        ?array $responsePayload = null,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): PosIntegrationEvent {
        return PosIntegrationEvent::create([
            'sale_id' => $sale?->id,
            'operation' => $operation,
            'provider' => $provider,
            'status' => $status,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'occurred_at' => now(),
        ]);
    }
}

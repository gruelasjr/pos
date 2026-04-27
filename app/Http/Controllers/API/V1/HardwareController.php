<?php

namespace App\Http\Controllers\API\V1;

use App\Services\Integrations\BarcodeScanner;
use App\Services\Integrations\CashDrawer;
use App\Services\Integrations\IntegrationEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HardwareController extends BaseApiController
{
    public function status(): JsonResponse
    {
        return $this->success('Estado de integraciones POS', [
            'payments' => config('pos_integrations.payments.driver'),
            'fiscal' => config('pos_integrations.fiscal.driver'),
            'receipt_printer' => config('pos_integrations.receipt_printer.driver'),
            'cash_drawer' => config('pos_integrations.cash_drawer.driver'),
            'barcode_scanner' => config('pos_integrations.barcode_scanner.driver'),
        ]);
    }

    public function parseBarcode(Request $request, BarcodeScanner $scanner): JsonResponse
    {
        $data = $request->validate([
            'input' => ['required', 'string', 'max:128'],
        ]);

        return $this->success('Codigo interpretado', $scanner->parse($data['input']));
    }

    public function openCashDrawer(
        Request $request,
        CashDrawer $drawer,
        IntegrationEventRecorder $events
    ): JsonResponse {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        $payload = [
            'reason' => $data['reason'] ?? 'manual',
            'user_id' => $request->user()?->id,
        ];
        $response = $drawer->open($payload);
        $provider = (string) ($response['provider'] ?? 'unknown');

        if (($response['ok'] ?? false) === true) {
            $events->success(null, 'cash_drawer.open_manual', $provider, $payload, $response);
        } else {
            $events->failure(null, 'cash_drawer.open_manual', $provider, $payload, 'cash_drawer_failed', $response);
        }

        return $this->success('Apertura de cajon solicitada', $response);
    }
}

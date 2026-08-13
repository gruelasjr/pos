<?php

/**
 * Controller: Sale endpoints (API v1).
 *
 * Handles sale creation, retrieval and reporting for the POS API.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * API controller for sales operations.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Jobs\SendReceiptJob;
use App\Models\Sale;
use App\Services\Integrations\SaleIntegrationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller for sales endpoints (list, show, receipt dispatch).
 */
/**
 * Sale controller.
 *
 * Manages sale retrieval, listing and receipt operations via the API.
 *
 * @package   App\Http\Controllers\API\V1
 */
class SaleController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Sale::query()
            ->with('customer', 'seller', 'warehouse');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('seller_id')) {
            $query->where('user_id', $request->input('seller_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->input('to'));
        }

        $sales = $query->orderByDesc('paid_at')->paginate($request->integer('per_page', 25));

        return $this->paginated($sales, 'Ventas listadas');
    }

    public function show(Sale $sale)
    {
        return $this->success(
            'Detalle de venta',
            $sale->load('items', 'customer', 'warehouse', 'seller', 'integrationEvents')
        );
    }

    public function sendReceipt(Request $request, Sale $sale, AuditLogger $auditLogger)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'destination' => ['required', 'string'],
        ]);

        $registrationToken = $sale->issueCustomerRegistrationToken();

        SendReceiptJob::dispatch($sale->id, [
            ...$data,
            'registration_token' => $registrationToken,
        ], (string) $sale->tenant_id);

        $auditLogger->log('sale.receipt_scheduled', $request->user(), Sale::class, $sale->id, [
            'channel' => $data['channel'],
            'destination' => $data['destination'],
        ]);

        return $this->success('Recibo programado', ['scheduled' => true]);
    }

    public function printReceipt(Sale $sale, SaleIntegrationService $integrations)
    {
        $response = $integrations->printReceipt($sale);

        return $this->success('Recibo enviado a impresora', $response);
    }

    public function issueFiscalDocument(
        Request $request,
        Sale $sale,
        SaleIntegrationService $integrations,
        AuditLogger $auditLogger
    ) {
        $data = $request->validate([
            'customer' => ['nullable', 'array'],
        ]);

        $response = $integrations->issueFiscalDocument($sale, [
            'fiscal' => [
                'issue_invoice' => true,
                'customer' => $data['customer'] ?? null,
            ],
        ]);

        $auditLogger->log('sale.fiscal_document_issued', $request->user(), Sale::class, $sale->id, [
            'fiscal_uuid' => $sale->refresh()->fiscal_uuid,
        ]);

        return $this->success('Documento fiscal emitido', $response);
    }
}

# POS Integrations

POS Faro exposes integration seams for payment providers, fiscal providers, receipt printers, cash drawers, and barcode scanners.

The default driver for every integration is `mock`, so local development, SQLite tests, and CI can exercise the full operational flow without external hardware or providers.

## Configuration

```env
POS_PAYMENT_DRIVER=mock
POS_FISCAL_DRIVER=mock
POS_FISCAL_ISSUE_ON_CHECKOUT=false
POS_RECEIPT_PRINTER_DRIVER=mock
POS_RECEIPT_PRINT_ON_CHECKOUT=true
POS_CASH_DRAWER_DRIVER=mock
POS_CASH_DRAWER_OPEN_ON_CASH_CHECKOUT=true
POS_BARCODE_SCANNER_DRIVER=mock
```

Failure simulation:

```env
POS_PAYMENT_MOCK_FAIL=false
POS_FISCAL_MOCK_FAIL=false
POS_RECEIPT_PRINTER_MOCK_FAIL=false
POS_CASH_DRAWER_MOCK_FAIL=false
```

Payment decline can also be simulated per checkout request:

```json
{
  "payment_method": "card",
  "payment_details": {
    "mock_decline": true
  }
}
```

## Contracts

- `App\Services\Integrations\PaymentGateway`
- `App\Services\Integrations\FiscalProvider`
- `App\Services\Integrations\ReceiptPrinter`
- `App\Services\Integrations\CashDrawer`
- `App\Services\Integrations\BarcodeScanner`

Provider implementations should return arrays with at least:

```json
{
  "ok": true,
  "provider": "provider-name",
  "status": "approved"
}
```

Failed provider responses should set `ok` to `false` and include `status`, `error_code`, and `message` when available.

## Checkout Flow

1. Create the sale shell and registration token.
2. Capture payment through `PaymentGateway`.
3. Create sale items and decrement inventory inside the transaction.
4. Optionally issue a fiscal document.
5. Print the local receipt through `ReceiptPrinter`.
6. Open the cash drawer for `cash` or `mixed` payments when configured.
7. Dispatch the existing receipt job for email/SMS delivery.

Payment failure returns HTTP 422 and rolls back sale creation/inventory changes.

## Operational Event Log

Every integration call writes a row to `pos_integration_events` with operation, provider, status, request payload, response payload, and error metadata when applicable.

Common operations:

- `payment.charge`
- `receipt.print`
- `cash_drawer.open`
- `cash_drawer.open_manual`
- `fiscal.issue`

## API Endpoints

All endpoints require bearer API auth.

```text
GET  /api/v1/hardware/status
POST /api/v1/hardware/barcode/parse
POST /api/v1/hardware/cash-drawer/open
POST /api/v1/sales/{sale}/print
POST /api/v1/sales/{sale}/fiscal-document
```

Barcode mock format:

```text
3*P1000
```

Returns `sku=P1000` and `quantity=3`.

## Adding A Real Provider

1. Implement the appropriate interface under `app/Services/Integrations`.
2. Add the driver name and class binding in `App\Providers\AppServiceProvider`.
3. Add required provider credentials to `config/pos_integrations.php` and `.env.example`.
4. Add feature tests that bind the provider fake or run against a sandbox.
5. Keep provider payloads idempotent by passing checkout `X-Idempotency-Key` downstream.

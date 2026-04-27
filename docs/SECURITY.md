# Security Model

## API Authentication

Clients authenticate through:

```http
POST /api/v1/auth/login
```

The response includes a SwiftAuth bearer token and `expires_at`. Protected `/api/v1/*` routes require:

```http
Authorization: Bearer <token>
```

The application middleware resolves the token to `App\Models\User`, checks that the account is active, records `last_used_at`, and then lets role middleware enforce access.

## Roles

The POS uses these role slugs:

- `admin`: full access.
- `vendedor`: cart and checkout operations.
- `auditor`: reporting and read-focused access.

Role checks compare both `slug` and `name` for compatibility with SwiftAuth data.

## Checkout Idempotency

Checkout requires:

```http
X-Idempotency-Key: <unique-client-generated-key>
```

The server stores the request hash and response body. Replaying the same key with the same request returns the original response. Reusing the key with a different payload returns `409`.

## Customer Registration Links

Receipt registration links no longer use sale IDs or folios as public tokens.

The sale stores:

- `customer_registration_token_hash`
- `customer_registration_expires_at`
- `customer_registration_used_at`

Only the plain token is sent to the customer. The API hashes the submitted token, requires it to be unexpired and unused, then marks it used after customer association.

## Logging And Audit

- `LOG_SENSITIVE=false` must remain the production default.
- `AuditLogger` writes to the SwiftAuth audit table.
- Payment details and token-like values are redacted before audit payloads are stored.

## Production Controls

- Use HTTPS only.
- Rotate provider credentials and SwiftAuth secrets on a schedule.
- Keep `APP_DEBUG=false` in production.
- Monitor failed jobs, 401/403 spikes, and idempotency hash mismatches.

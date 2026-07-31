# Security Model

## Identity and tenant boundary

Caronte is the sole identity authority. Browser routes use the Caronte OIDC session middleware, user APIs accept Caronte Bearer tokens, and machine APIs use Caronte application authentication with a required tenant. Tokens must validate signature through JWKS, issuer, audience/client, expiry, and TLS.

Bee-Hive resolves the tenant with `CaronteTenantResolver`. Every business record and uniqueness constraint is tenant-scoped. Model binding and authorization deliberately return 404 for cross-tenant resources. Jobs carry a tenant ID explicitly and must establish and clear tenant context around every execution.

The local POS user is a shadow identity keyed by `(tenant_id, caronte_uri_user)`. It stores only profile/role data needed for history and relationships; passwords and Caronte tokens are never persisted there.

## Roles

- `pos-admin`: full tenant administration.
- `pos-seller`: cash-session, customer, cart, and sale operations.
- `pos-auditor`: read-only reporting and audit access.

Role claims are synchronized from Caronte; local role escalation is not authoritative.

## Checkout and public links

Checkout requires `X-Idempotency-Key`. A retry with the same tenant, actor, route, key, and request returns the original result; conflicting payloads return 409. Payment attempts are durable and providers must support idempotency and status lookup.

Customer-registration URLs use opaque random tokens. Only a token hash, tenant association, expiry, and consumption timestamp are stored. Tenant context is established from a valid global link before any sale/customer query.

## Production controls

- Keep `APP_DEBUG=false`, `LOG_SENSITIVE=false`, HTTPS and Caronte certificate verification enabled.
- Never configure `BEE_HIVE_STATIC_TENANT_ID` or mock/stub providers in production.
- Redact credentials, authorization headers, payment data, and registration tokens from logs and audit payloads.
- Monitor authentication failures, cross-tenant denials, idempotency conflicts, payment reconciliation, outbox DLQ depth, and readiness failures.
- Rotate Caronte and provider credentials through the deployment secret store and exercise revocation procedures.

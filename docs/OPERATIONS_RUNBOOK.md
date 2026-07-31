# POS Faro operations runbook

## Service objectives

- Checkout availability: 99.9% monthly, excluding declared maintenance.
- Checkout API latency: p95 below 800 ms for cash sales; external payment latency is reported separately.
- Recovery point objective (RPO): 15 minutes.
- Recovery time objective (RTO): 60 minutes.
- No sale may transition to `paid` without a durable payment result. `reconciliation_required` is an alertable state, never an implicit success.

## Production topology

Run the immutable application artifact behind HTTPS with MySQL 8.4 and Redis 7. Use separate processes for HTTP, `queue:work`, and `schedule:work`. Run at least two queue workers and restart them after every deployment. Secrets for Caronte and providers must come from the deployment secret store, never the image or repository.

Required production settings include `APP_ENV=production`, `APP_DEBUG=false`, Redis-backed cache/session/queue, TLS verification for Caronte, OIDC issuer enforcement, and non-mock drivers for every enabled integration. The application startup guard intentionally refuses unsafe provider configuration.

## Deployment

1. Build once with `composer install --no-dev --classmap-authoritative`, `npm ci`, `npm run build`, and the full CI gate.
2. Back up the database and record the artifact and migration versions.
3. Put one instance in maintenance/drain mode and run `php artisan migrate --force` from the release artifact.
4. Run `php artisan optimize`, restart queue workers, and probe `/up` and `/ready`.
5. Send a Caronte-authenticated read request, open a sandbox cash session, and complete the provider sandbox smoke transaction.
6. Shift traffic gradually. Watch error rate, latency, failed jobs, payment reconciliation, outbox lag, and tenant-isolation alerts for at least 15 minutes.

Rollback uses the previous immutable artifact. Database rollback is allowed only when the migration is explicitly marked reversible and no new-version writes have occurred; otherwise roll forward with a corrective migration.

## Backup and restore

- Take encrypted MySQL point-in-time backups at least every 15 minutes and a full daily backup. Retain daily backups for 35 days and monthly backups for 12 months.
- Back up receipt/export object storage with versioning and lifecycle retention. Redis is disposable and must not be the only store of business state.
- Perform a restore drill in an isolated account every quarter. Verify row counts, tenant distribution, referential integrity, latest paid sales, payment attempts, and outbox state.
- Record achieved RPO/RTO, evidence, and corrective actions. A failed restore drill blocks the next production release.

## Monitoring and alerts

Every request, job, payment attempt, and outbox message must carry `request_id`, `tenant_id`, and the relevant sale/payment identifier. Never log tokens, credentials, PAN, CVV, or unredacted provider payloads.

Page the on-call engineer for:

- checkout 5xx above 1% for five minutes;
- any sustained `reconciliation_required` payment or duplicate idempotency violation;
- queue/outbox oldest-message age above five minutes;
- failed jobs increasing for ten minutes;
- database, Redis, Caronte, or required-provider readiness failure;
- authentication or cross-tenant-denial anomalies above the established baseline.

## Incident priorities

1. Stop or disable only the affected payment method; preserve cash operation when safe.
2. Never retry an unknown external charge with a new idempotency key. Query/reconcile the original provider attempt.
3. Preserve audit, payment-attempt, outbox, and correlation records.
4. For suspected tenant leakage, disable the affected route or service immediately and treat the incident as a security breach.
5. Reprocess outbox/DLQ entries only through the tenant-aware administrative command after the root cause is fixed.

# POS Faro — Execution Plan P0 → P2

## Estado actual (ejecutado en este ciclo)

### P0 completado en código

- API v1 habilitada en bootstrap (`bootstrap/app.php`).
- Rutas públicas separadas para `POST /api/v1/auth/login` y `POST /api/v1/customers/register`.
- RBAC por middleware `role` aplicado por endpoint en rutas API v1.
- Auditoría conectada en eventos críticos (producto, inventario, checkout, recibos, clientes).
- Pruebas unitarias pasando (`vendor/bin/phpunit -c phpunit.xml`).

## Objetivo general

Construir capacidades de POS con paridad competitiva y diferenciadores en resiliencia, velocidad de operación y control comercial.

---

## P1 — Paridad comercial fuerte (6–8 semanas)

### Épica P1.1 — Devoluciones y cambios

**Resultado esperado**: registrar devoluciones parciales/totales con trazabilidad y ajuste de inventario.

#### Historias

1. Como cajero, puedo devolver uno o más renglones de una venta con motivo.
2. Como admin, puedo aprobar devoluciones fuera de ventana permitida.
3. Como auditor, puedo ver historial completo de devoluciones sin editar.

#### Implementación técnica

- Crear entidades: `return_notes`, `return_items`.
- Endpoints:
    - `POST /api/v1/sales/{sale}/returns`
    - `GET /api/v1/returns`
    - `GET /api/v1/returns/{id}`
- Servicio transaccional: `ReturnService`.
- Reglas:
    - no devolver más cantidad que la vendida,
    - actualizar inventario del almacén origen,
    - generar evento de auditoría.

#### DoD

- Cobertura de pruebas de dominio para casos parcial/total/inválido.
- Auditoría obligatoria en alta y cancelación.
- Reporte diario incluye devoluciones netas.

---

### Épica P1.2 — Corte y arqueo de caja

**Resultado esperado**: apertura/cierre por turno con diferencias de efectivo registradas.

#### Historias

1. Como vendedor, puedo abrir caja con fondo inicial.
2. Como vendedor, puedo cerrar turno y capturar conteo final.
3. Como admin, puedo auditar diferencias por caja/usuario/turno.

#### Implementación técnica

- Entidades: `cash_sessions`, `cash_movements`.
- Endpoints:
    - `POST /api/v1/cash-sessions/open`
    - `POST /api/v1/cash-sessions/{id}/close`
    - `GET /api/v1/cash-sessions`
- En checkout, asociar `sale.cash_session_id`.
- Reglas:
    - una caja activa por vendedor,
    - no cerrar con ventas pendientes en estado inconsistente.

#### DoD

- Dashboard muestra cajas abiertas y diferencias.
- Export CSV de cierres por periodo.

---

### Épica P1.3 — Motor de promociones

**Resultado esperado**: reglas promocionales configurables sin alterar código.

#### Historias

1. Como admin, puedo crear promociones por porcentaje/monto/2x1/combo.
2. Como cajero, el carrito aplica automáticamente la mejor promoción válida.

#### Implementación técnica

- Entidades: `promotions`, `promotion_rules`, `promotion_scopes`.
- Servicio: `PromotionEngine` (determinístico y trazable).
- Integrar cálculo en `CartService` previo a totales.
- Guardar en carrito: `applied_promotions` (json).

#### DoD

- Prioridad y exclusiones reproducibles.
- Pruebas de regresión con matriz de casos.

---

### Épica P1.4 — Exportaciones y UX de reportes

**Resultado esperado**: exportación operacional y financiera lista para administración.

#### Implementación técnica

- Endpoints:
    - `GET /api/v1/reports/daily/export?format=csv`
    - `GET /api/v1/reports/by-seller/export?format=csv`
- Job de exportación asíncrona para rangos grandes.
- Registro de auditoría de exportaciones.

#### DoD

- Descarga directa para archivos pequeños.
- Link temporal firmado para archivos grandes.

---

## P2 — Diferenciación de mercado (10–14 semanas)

### Épica P2.1 — Offline-first POS

**Resultado esperado**: operar ventas sin conexión y reconciliar sin duplicados.

#### Implementación técnica

- PWA + service worker para assets y catálogos.
- Cola local (`indexedDB`) para operaciones de carrito/checkout.
- Idempotency key por operación (`X-Idempotency-Key`).
- Endpoint idempotente para checkout.

#### DoD

- Pruebas de reconexión con conflicto resuelto.
- No hay doble cargo ni doble decremento de inventario.

---

### Épica P2.2 — Lealtad y CRM transaccional

**Resultado esperado**: puntos, cupones y campañas basadas en comportamiento.

#### Implementación técnica

- Entidades: `loyalty_accounts`, `loyalty_movements`, `coupons`.
- Endpoints para acumulación/canje/consulta de saldo.
- Integración en checkout (acumular/canjear con reglas).

#### DoD

- Trazabilidad total por cliente y venta.
- Simulación de fraude básica (doble canje bloqueado).

---

### Épica P2.3 — Integraciones enterprise

**Resultado esperado**: conectores para pagos, fiscal y ecosistema.

#### Implementación técnica

- Adaptadores `PaymentGateway`, `FiscalProvider`, `ERPConnector`.
- Outbox pattern para integración confiable.
- Retries con backoff y DLQ.

#### DoD

- Métricas de éxito por proveedor.
- Reprocesamiento seguro de transacciones fallidas.

---

## Matriz de priorización

| Iniciativa               | Impacto negocio | Complejidad | Prioridad |
| ------------------------ | --------------- | ----------- | --------- |
| Devoluciones             | Alto            | Media       | Alta      |
| Corte de caja            | Alto            | Media       | Alta      |
| Promociones              | Alto            | Alta        | Alta      |
| Exportaciones            | Medio           | Baja        | Media     |
| Offline POS              | Muy alto        | Muy alta    | Alta      |
| Lealtad                  | Alto            | Alta        | Media     |
| Integraciones enterprise | Muy alto        | Muy alta    | Alta      |

---

## KPIs de éxito por fase

### P1

- `checkout_p95_ms < 800`
- `cart_error_rate < 0.5%`
- `refund_processing_time < 120s`
- `cash_discrepancy_rate < 1%`

### P2

- `offline_checkout_success_rate > 99%`
- `reconciliation_conflict_rate < 0.5%`
- `customer_repeat_rate +10%`
- `stockout_events -20%`

---

## Plan de sprint recomendado

### Sprint 1–2

- Devoluciones (modelo + API + UI básica).
- Corte de caja (apertura/cierre + reportes).

### Sprint 3–4

- Promociones (motor + reglas + UI admin).
- Exportaciones CSV + auditoría.

### Sprint 5–7

- Offline-first base (PWA + cache + cola local).
- Idempotencia de checkout y reconciliación.

### Sprint 8–10

- Lealtad y cupones.
- Integraciones enterprise prioritarias.

---

## Riesgos y mitigaciones

- **Riesgo**: complejidad de reconciliación offline.
    - **Mitigación**: idempotencia obligatoria y eventos con versionado.
- **Riesgo**: promociones ambiguas.
    - **Mitigación**: prioridad explícita y motor determinístico testeado.
- **Riesgo**: deuda por integraciones.
    - **Mitigación**: contratos por interfaz + pruebas de contrato.

---

## Checklist de readiness para “best-in-class”

- [ ] Devoluciones en producción con trazabilidad completa.
- [ ] Corte de caja operativo por turno y auditoría.
- [ ] Motor de promociones estable y auditable.
- [ ] Reportes exportables en CSV con historial.
- [ ] POS offline con reconciliación idempotente.
- [ ] Lealtad/cupones funcionando con controles antifraude.
- [ ] Integraciones críticas (pago/fiscal) con SLA medible.
- [ ] Observabilidad con alertas de negocio y técnicas.

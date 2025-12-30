# Project Requirements & Technical Specification

## 1. Scope and Goals

POS Faro is a point of sale system with:

-   Inventory management with reorder points and automatic stock depletion timestamps
-   Multi-warehouse, multi-seller support
-   Catalog tooling (products, types, SKU ranges)
-   Customer capture with marketing consent
-   Daily, weekly, monthly, and seller-focused reports
-   Security, audit trails, and transactional consistency

## 2. Technical Stack

**Backend**: Laravel 12 (PHP 8.3+)  
**Frontend**: Inertia.js + React 19, TailwindCSS 4, Atomic Design System (ADS)  
**Database**: MySQL 8.x (InnoDB, utf8mb4, strict mode)  
**Auth**: `equidna/swift-auth` guard and token issuer  
**Utilities**: `equidna/laravel-toolkit` for responses; Folio generator; receipt renderer  
**Build**: Vite, npm 10+, Composer 2.5+  
**Logging**: JSON structured logging, request-id correlation, audit trails  
**i18n**: ES-MX default; i18n keys prepared on frontend

## 3. Core Entities

### Almacen (Warehouse)

-   `id` (uuid), `nombre`, `codigo` (unique), `activo`, timestamps

### TipoProducto (Product Type)

-   `id`, `nombre`, `codigo` (unique), timestamps

### Producto (Product)

-   `id`, `sku` (unique, auto-generated if blank), `descripcion_corta`, `descripcion_larga`, `foto_url`
-   `precio_compra`, `precio_venta`, `fecha_ingreso`, `fecha_fin_stock` (auto-set at 0 stock)
-   `tipo_producto_id` (fk), `activo`, timestamps
-   **Rules**: SKU must not collide with reserved ranges; auto-generated on creation

### Inventario (Inventory)

-   `id`, `producto_id`, `almacen_id`, `existencias`, `punto_reorden` (reorder point), timestamps
-   **Indices**: Unique (producto_id, almacen_id); search by descripcion_corta

### RangoSKUReservado (Reserved SKU Range)

-   `id`, `prefijo`, `desde`, `hasta`, `usado_hasta`, `proposito`, timestamps
-   **Purpose**: Prevent SKU collisions; service allocates N SKUs and advances `usado_hasta`

### Carrito (Shopping Cart)

-   `id`, `clave_visual` (visual key for UI), `vendedor_id` (fk), `almacen_id`
-   `estado` (enum: activo, en_pausa, cerrado), `total_bruto`, `descuento_total`, `total_neto`, timestamps
-   **Rules**: Each seller can have multiple simultaneous carts

### CarritoItem

-   `id`, `carrito_id`, `producto_id`, `cantidad` (>0), `precio_unitario` (snapshot), `descuento`, `subtotal` (derived)

### Venta (Sale)

-   `id`, `folio` (unique sequential per warehouse), `almacen_id`, `vendedor_id`
-   `cliente_id` (nullable), `metodo_pago` (enum: efectivo, tarjeta, transferencia, mixto)
-   `total_bruto`, `descuento_total`, `total_neto`, `pagado_en`, timestamps

### VentaItem

-   `id`, `venta_id`, `producto_id`, `sku` (snapshot), `descripcion`, `cantidad`, `precio_unitario`, `descuento`, `subtotal`

### Cliente (Customer)

-   `id`, `nombre`, `email` (nullable), `telefono` (nullable), `acepta_marketing` (bool), timestamps

### Usuario (User)

-   Managed by `equidna/swift-auth`: `id`, `nombre`, `email`, `rol` (admin, vendedor, auditor)

## 4. Business Rules

**SKU Generation**

-   If not provided, auto-generate
-   Never collide with reserved ranges; a service generates N SKUs and advances `usado_hasta`

**Inventory**

-   Venta decrements `Inventario.existencias` for the sale warehouse
-   When existencias reaches 0, auto-set `Producto.fecha_fin_stock` if empty
-   Sale confirmation and inventory deduction are atomic (same transaction)

**Discounts**

-   Applied per-item and per-cart/sale
-   No negative totals allowed

**Simultaneous Carts**

-   Each seller can maintain multiple carts with `clave_visual` for quick switching

**Payment Methods**

-   Supported: cash, card, transfer, mixed (mixed requires component breakdown)

**Receipts**

-   PDF/HTML generation via job queue
-   Email/SMS delivery using adapters (simulated if no real gateway)
-   Include registration token link: `/r/{token}` for customer self-registration

**Audit**

-   Log events: product creation/edit, inventory changes, sale confirmation/cancellation

**RBAC (Role-Based Access Control)**

-   **Admin**: full access
-   **Vendedor**: POS, customers, catalog read-only
-   **Auditor**: read-only all; no mutations

## 5. API Endpoints (/api/v1)

**Authentication**: `Authorization: Bearer <token>`  
**Response Format**: `{ success: bool, data, error: {code, message, details?} }`  
**Pagination**: `page`, `per_page`, `total`

### Auth

-   `POST /auth/login {email, password}` → `{token}`

### Warehouses

-   `GET /warehouses`, `POST`, `PATCH /warehouses/{id}`

### Product Types

-   `GET /product-types`, `POST`, `PATCH`

### Products

-   `GET /products?query=&tipo_id=&almacen_id=`
-   `POST /products` (sku auto-generated if blank)
-   `PATCH /products/{id}`, `GET /products/{id}`

### Inventory

-   `GET /inventory?almacen_id=&producto_id=`
-   `PATCH /inventory/adjust {producto_id, almacen_id, delta, motivo}`

### SKU Generator

-   `POST /skus/reserve {cantidad, prefijo?}` → `{skus: [...], rango_id}`

### Carts (POS)

-   `GET /carts?estado=activo`, `POST /carts {almacen_id}` → `{id, clave_visual}`
-   `POST /carts/{id}/items {producto_id, cantidad, precio_unitario?, descuento?}`
-   `PATCH /carts/{id}/items/{item_id} {cantidad?, descuento?}`
-   `DELETE /carts/{id}/items/{item_id}`
-   `PATCH /carts/{id} {descuento_total?, estado?}`
-   `POST /carts/{id}/checkout {metodo_pago, pagos_detalle?, cliente_id?}` → creates Sale, decrements inventory, closes cart

### Sales

-   `GET /sales?desde=&hasta=&almacen_id=&vendedor_id=`
-   `GET /sales/{id}`
-   `POST /sales/{id}/receipt {canal: email|sms, destino}`

### Customers

-   `GET /customers?query=`, `POST /customers {nombre, email?, telefono?, acepta_marketing}`
-   `PATCH /customers/{id}`
-   `POST /customers/register {token, ...}` (self-registration via receipt link)

### Reports

-   `GET /reports/daily?fecha=&almacen_id=&tipo_id=`
-   `GET /reports/weekly?semana=&comparar=1`
-   `GET /reports/monthly?mes=&comparar=1`
-   `GET /reports/by-seller?desde=&hasta=&almacen_id=`

## 6. UI Flows (Inertia + React with ADS)

**Login** (`/login`)

-   Email/password form with standard error handling

**Dashboard** (`/`)

-   Four StatCards: daily sales, weekly totals, inventory alerts, active sellers
-   Top sellers table ranked by amount
-   Inventory alerts table (at/below reorder point)
-   Auto-refresh on load

**Catalog: Products** (`/catalogo/productos`)

-   New product form: descriptions, prices, entry date, product type
-   Auto SKU generation if field left blank
-   Product table with pagination, SKU, description, price, type, status

**Catalog: Warehouses** (`/catalogo/almacenes`)

-   Form: name, code, active toggle
-   Warehouse list table

**Customers** (`/clientes`)

-   Form: name, email, phone, marketing consent
-   Customer table with search

**POS** (`/pos`)

-   Cart bootstrap (load carts, warehouses)
-   Product search (SKU/description with 300ms debounce, top 5 matches)
-   Add/edit/remove items with quantity steppers
-   Discounts at item and cart level
-   Payment method selection (cash, card, transfer, mixed)
-   Checkout triggers inventory deduction (atomic)
-   Success alert shows folio; enqueues receipt job

**Reports** (`/reportes`)

-   Date and warehouse filters apply to all tabs
-   Daily tab: date, net total, ticket count
-   Weekly tab: current vs. previous week chart
-   Monthly tab: selected month totals
-   By Seller tab: seller, ticket count, total sold
-   Update button recalculates

## 7. Integrations & Adapters

-   **Email**: Mailer interface with SMTP local and stub implementations
-   **SMS**: SmsProvider interface with stub default
-   **Image Storage**: Local disk (development), S3-compatible (production)

## 8. Security & Compliance

-   Strict backend and frontend validation
-   Input sanitization, rate limiting on sensitive endpoints
-   CSRF on web routes; CORS configured for future apps
-   Audit logs: user, IP, minimal non-sensitive payload

## 9. Performance & Scalability

-   Indexes on primary searches
-   Jobs queue for receipt delivery
-   Pagination on all listings
-   Atomic transactions for checkout

## 10. Migrations & Seeds

-   Migrations for all entities
-   Seeders: roles, admin user, primary warehouse, demo product types

## 11. Acceptance Criteria

-   Create N reserved SKUs; auto-generate product SKU without collision
-   Create product, upload photo from camera/library
-   Create two carts, alternate between them, apply discounts, confirm mixed-payment sale
-   Confirm sale decrements correct warehouse inventory; sets fecha_fin_stock at 0
-   Send receipt email/SMS successfully using stubs
-   Reports show daily/weekly/monthly/seller totals with filters

## 12. Out of Scope (v1)

-   Fiscal invoicing
-   Multi-rate taxes
-   Returns and exchanges
-   Integrated payment terminal

## 13. Roadmap (v1.1+)

-   Returns with credit notes
-   Loyalty points and real transactional notifications
-   Mobile app (same API)

## 14. Implementation Notes

-   Use DB transactions in `/carts/{id}/checkout`
-   Normalize prices with fixed decimals; avoid floats
-   Consistent responses via `equidna/toolkit`
-   Centralize folio generation per warehouse via sequence table

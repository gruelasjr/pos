# POS Faro — User Guide

## Audience & Prerequisites

-   Users with Swift Auth accounts assigned one of: Admin, Vendedor, Auditor roles
-   Devices: Desktop browsers (Chrome 120+, Edge, Safari 17+) or tablets (min 1280×800 viewport)
-   Network: Secure connection to Laravel API host (default: `http://localhost:8000`)
-   Optional: Barcode scanner that inputs into focused text fields

## Supported Browsers

-   **Chrome/Edge 120+** or **Safari 17+** (recommended)
-   **Firefox** works but is not officially styled
-   **Requirements**: Cookies and local storage enabled; pop-ups allowed for receipt printing

## Session Management

Session credentials stored in local storage:

-   `pos-token`: Bearer token for API authentication
-   `pos-user`: Logged-in user metadata

## Login

1. Browse to `/login`
2. Enter your email and password (as provided by Admin)
3. Click **Entrar**
4. Success redirects to Dashboard with token stored; on error: "Credenciales invalidas"
5. Contact an Admin to reset password if needed

## Application Layout

**Sidebar (left)**: Main navigation

-   Dashboard, Productos, Almacenes, POS, Clientes, Reportes, **Ir a cajas** (quick jump), **Salir** (logout)

**Header (top)**: Page title, logged-in user email

**Content**: Selected page content

**Logout**: Click **Salir** in sidebar footer (clears local storage, redirects to `/login`)

## Dashboard

Path: `/`

### Cards (Auto-refresh on Load)

-   **Daily Sales** (today's net total)
-   **Weekly Total**
-   **Inventory Alerts** (at/below reorder point)
-   **Active Sellers**

### Tables

-   **Top Vendedores**: Ranked by sales amount
-   **Alertas de Inventario**: Products at/below reorder points

Use browser refresh for updated data.

## Catalog: Products

Path: `/catalogo/productos`

### New Product Form (Left Side)

-   **Descripción Corta** (required)
-   **Descripción Larga** (optional)
-   **Precio Compra** (required)
-   **Precio Venta** (required)
-   **Fecha de Ingreso** (defaults to today)
-   **Tipo de Producto** (dropdown from API)
-   **Código SKU** (optional; auto-generated if left blank)

Click **Guardar** to create.

### Product Table (Right Side)

Shows: SKU, Description, Sale Price, Type, Active status  
Pagination controlled via `per_page` query parameter (default 50)

## Catalog: Warehouses

Path: `/catalogo/almacenes`

### New Warehouse Form

-   **Nombre** (required)
-   **Código** (required, unique)
-   **Activo** (toggle)

Click **Guardar** to create.

### Warehouse Table

Lists all warehouses and status (Activo/Inactivo).

## Customers

Path: `/clientes`

### New Customer Form

-   **Nombre** (required)
-   **Correo** (optional)
-   **Teléfono** (optional)
-   **Acepta Marketing** (toggle)

Click **Guardar** to create.

### Customer Table

Displays name, email, phone, marketing consent (Sí/No).

### Self-Registration

Customers can self-register via receipt link `/r/{token}` sent after checkout.

## POS (Point of Sale)

Path: `/pos`

### Bootstrap

-   Loads your active carts and warehouse list
-   Select warehouse from **Crear carrito** dropdown to open new cart
-   Each cart tied to the logged-in seller

### Search & Add Products

1. Start typing in **Buscar producto** (SKU or description)
2. After 300ms, client queries `/api/v1/products` and shows top 5 matches
3. Click match or press Enter → adds product with quantity 1
4. Item appears in **Carrito actual**

### Manage Items

| Action              | How                                                   |
| ------------------- | ----------------------------------------------------- |
| **Change Quantity** | Use stepper input (triggers PATCH to update cart)     |
| **Apply Discount**  | Enter amount in **Descuento Total (MXN)**, click away |
| **Remove Item**     | Click trash icon next to row (DELETE from cart)       |

### Checkout

1. Select **Método de Pago**: Efectivo, Tarjeta, Transferencia, o Mixto
2. Enter optional payment breakdown
3. Click **Cobrar**
4. On success:
    - Alert shows folio number
    - Payment form resets
    - Cart list reloads
    - Receipt job enqueued (email/SMS delivery)

### Receipts

Automated delivery via background job queue.  
Manual resend: `POST /api/v1/sales/{sale_id}/receipt {canal, destino}`

## Reports

Path: `/reportes`

### Filters

-   **Date Picker** (applies to all tabs)
-   **Warehouse Dropdown** (applies to all tabs)

Click **Actualizar** after adjusting filters.

### Tabs

| Tab              | Content                                         |
| ---------------- | ----------------------------------------------- |
| **Diario**       | Date, net total, ticket count for selected date |
| **Semanal**      | Chart: current week vs. previous week           |
| **Mensual**      | Selected month totals and breakdown             |
| **Por Vendedor** | Seller name, ticket count, total sold           |

## Troubleshooting

| Issue                                        | Solution                                                                                                                             |
| -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| **Login loops back to `/login`**             | Token expired or cleared. Re-enter credentials; ensure local storage enabled                                                         |
| **Product search returns nothing**           | Confirm SKU exists in Catalog > Productos. Search only checks `sku` and `descripcion_corta`                                          |
| **Checkout fails (inventario_insuficiente)** | Quantity exceeds warehouse stock. Adjust stock via Admin panel or inventory adjust API                                               |
| **No receipt delivery**                      | Verify queue worker (`php artisan queue:work`) is running and email/SMS env vars configured. Stubs log to `storage/logs/laravel.log` |
| **Reports show zero data**                   | Run migrations + seeds (`php artisan migrate --seed`). Confirm sales exist. Try clearing filters (they are restrictive)              |

## Getting Help

-   **Technical issues**: File ticket in issue tracker
-   **Account/password**: Contact an Admin user
-   **Features**: See `docs/REQUIREMENTS.md` for roadmap

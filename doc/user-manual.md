# POS Faro User Manual

This guide explains how store staff and managers operate the POS Faro web application. Screenshots are not embedded, but every step references the exact labels that appear in the React UI under `resources/js/Pages`.

## Audience and prerequisites

- Users with Swift Auth accounts and one of the roles seeded by `DatabaseSeeder` (Admin, Vendedor, Auditor).
- Devices: recent desktop browsers (Chrome, Edge, Safari) or tablets with a minimum 1280x800 viewport.
- Network: secure connection to the Laravel API host (defaults to `http://localhost:8000` during development).
- Hardware: optional barcode scanner that inputs into focused text fields.

## Supported browsers and settings

- Chrome/Edge 120+ or Safari 17+. Firefox works but is not officially styled with HeroUI.
- Allow cookies and local storage. Session state uses local storage keys `pos-token` and `pos-user`.
- Enable pop ups for `localhost` if you plan to print receipts in a new tab.

## Login flow

1. Browse to `/login`.
2. Enter your email and password exactly as provided by the admin.
3. Click **Entrar**. A success message redirects to the dashboard and stores your bearer token.
4. If you mistype the credentials you will see `Credenciales invalidas`. Retry or contact an admin to reset the password.

## Application layout

- **Sidebar** (left) lists the main work areas: Dashboard, Productos, Almacenes, POS, Clientes, Reportes.
- **Header** (top) shows the current page title, the signed in user email, and a quick link labeled **Ir a cajas** that jumps to the POS module.
- **Content area** renders the selected page (see sections below).
- Use the **Salir** button in the sidebar footer to logout and clear local storage.

## Dashboard

Path: `/`

- Four StatCards summarize daily sales, weekly totals, inventory alerts, and active sellers. Values come from `/api/v1/reports`.
- **Top vendedores** table ranks sellers by sales amount.
- **Alertas de inventario** table lists products whose stock is at or below the reorder point.
- Indicators refresh automatically on load; use browser refresh for updated data.

## Catalog: Products

Path: `/catalogo/productos`

1. Left side form (**Nuevo producto**) lets you capture:
   - Short and long descriptions.
   - Purchase and sale prices.
   - Entry date (defaults to today).
   - Product type (dropdown populated from `/api/v1/product-types`).
2. Click **Guardar** to submit. The API auto generates an SKU if the `sku` field is left blank.
3. Right side table shows SKU, description, sale price, type, and active flag. Use pagination controls from the backend response if you have more than 50 entries (adjust `per_page` query param when needed).
4. Editing existing products is handled via the API (PATCH `/api/v1/products/{id}`) and will be exposed in the UI later.

## Catalog: Warehouses

Path: `/catalogo/almacenes`

- Form captures **Nombre**, **Codigo**, and **Activo** toggle.
- Submit with **Guardar** to create a warehouse via `POST /api/v1/warehouses`.
- Table on the right lists all warehouses and their status.

## Customers

Path: `/clientes`

- Form captures **Nombre**, **Correo**, **Telefono**, and **Acepta marketing**.
- Click **Guardar** to create a record via `POST /api/v1/customers`.
- Table lists current customers. Marketing column shows `Si` or `No`.
- Remote self registration is available through `/api/v1/customers/register` when including the sale token in receipts. The UI exposes status when a receipt link is consumed.

## POS workspace

Path: `/pos`

### Bootstrap

- The page loads your carts (`GET /api/v1/carts?per_page=50`) and active warehouses.
- Select a warehouse from the **Crear carrito** dropdown to open a new cart. Each cart is tied to the seller who created it.

### Search and add products

1. In the **Buscar producto** field start typing an SKU or description. After 300 ms the client calls `/api/v1/products` with `query` and shows the top 5 matches.
2. Click a suggested item or press Enter to focus the list. The API call `POST /carts/{cart}/items` adds the product with quantity `1`.
3. Items appear under **Carrito actual**. Each row shows description, price, quantity, subtotal, and a stepper.

### Update items

- Use the quantity input to increase or decrease units. This triggers `PATCH /carts/{cart}/items/{itemId}`.
- Discounts: enter a number in the **Descuento total (MXN)** input and click away. The cart service recalculates totals and enforces non negative net amounts.
- Remove an item by clicking the trash icon next to the row (calls `DELETE /carts/{cart}/items/{itemId}`).

### Checkout

1. In the **Pagos** card pick a method: cash, card, transfer, or mixed.
2. Enter optional payment details (for mixed payments include a breakdown).
3. Click **Cobrar**. The frontend posts to `POST /carts/{cart}/checkout` with payment metadata.
4. Successful checkout shows an alert with the folio number, resets the payment form, reloads carts, and enqueues `SendReceiptJob`.

### Receipts

- Receipts are delivered via email or SMS when `SendReceiptJob` runs. Admins can resend from the ventas module once UI hooks are added. For now, trigger `POST /api/v1/sales/{sale}/receipt`.

## Reports

Path: `/reportes`

- Filters: date picker and warehouse dropdown apply to all tabs.
- Tabs:
  - **Diario** shows date, total net, and ticket count.
  - **Semanal** renders a chart comparing current versus previous week.
  - **Mensual** displays the selected month and totals.
  - **Por vendedor** table lists seller, ticket count, and total sold.
- Use the **Actualizar** button after adjusting filters.

## Logging out

- Click **Salir** in the sidebar footer. This clears `pos-token` and `pos-user` from local storage and redirects to `/login`.

## Troubleshooting

| Symptom | Resolution |
| ------- | ---------- |
| Login succeeds but immediately redirects back to `/login` | Token expired or cleared. Re enter credentials and ensure the browser allows local storage. |
| Product search returns nothing | Confirm the SKU exists under Catalog > Productos. Remember search only looks at `short_description` and `sku`. |
| Checkout fails with `inventario_insuficiente` | The backend blocks sales when requested quantity exceeds inventory for the selected warehouse. Adjust stock via `/api/v1/inventory/adjust` or restock through the admin panel. |
| No receipts are sent | Verify the queue worker (`php artisan queue:work`) is running and that `SMS_FROM`/`MAIL_*` variables are configured. During development the stubs log to `storage/logs/laravel.log`. |
| Reports show zero data | Ensure `php artisan migrate --seed` has been run and that there are confirmed sales. Filters are restrictive (date, warehouse), so clear them to see all data. |

## Getting help

- Technical issues: file a ticket in your issue tracker or mention the maintainer in chat.
- Account problems: contact an Admin role user who can reset passwords via Swift Auth.
- Feature requests: consult `doc/requirements.md` for roadmap context and capture proposals there before implementation.

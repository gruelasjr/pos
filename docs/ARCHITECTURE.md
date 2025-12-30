# POS Faro — Architecture & Deployment

## Project Overview

**POS Faro** is a Laravel 12 + Inertia/React point of sale platform for multi-warehouse retailers. It provides inventory management, catalog tooling, real-time cart management, and comprehensive reporting.

## Technology Stack

| Layer             | Technology                                                      |
| ----------------- | --------------------------------------------------------------- |
| **Backend**       | Laravel 12 (PHP 8.3), MySQL 8.x, Redis (optional)               |
| **Frontend**      | React 19, Inertia.js, TailwindCSS 4, Atomic Design System (ADS) |
| **Build**         | Vite, npm 10+, Composer 2.5+                                    |
| **Auth**          | `equidna/swift-auth` guard, JWT tokens                          |
| **Queue**         | Redis or database driver for background jobs                    |
| **Logging**       | Structured JSON logs, request-id correlation                    |
| **UI Components** | Custom ADS (atoms, molecules, organisms)                        |

## Directory Structure

### Backend

```
app/
├── Domain/              # Business logic layers
│   ├── Catalog/         # Product, warehouse, type services
│   ├── Inventory/       # Stock management
│   ├── POS/             # Cart, checkout, folio generation
│   ├── Sales/           # Sale, item, customer services
│   └── Shared/          # Domain events, exceptions
├── Http/
│   ├── Controllers/API/V1/  # JSON API endpoints
│   └── Middleware/          # Auth, CORS, audit logging
├── Jobs/                    # Queue: SendReceiptJob
├── Models/                  # Eloquent (Product, Sale, etc.)
├── Providers/               # Service registration
└── Support/                 # Helpers: AuditLogger, FolioGenerator, ReceiptRenderer

config/
├── app.php              # App name, timezone, providers
├── cache.php            # Cache driver config
├── database.php         # DB connection settings
├── security.php         # CORS, rate limiting, CSRF
├── services.php         # Email, SMS provider setup
└── ...

database/
├── migrations/          # Schema definitions
├── factories/           # Model factories for seeding
└── seeders/             # Database seeds (roles, admin, demo data)

routes/
├── api.php              # /api/v1/* endpoints
├── web.php              # Web routes (Inertia pages)
└── console.php          # Artisan commands
```

### Frontend

```
resources/
├── js/
│   ├── app.jsx              # Vite entry point
│   ├── components/
│   │   ├── atoms/           # Button, Card, Text, Input, etc.
│   │   ├── molecules/       # FormField, Modal, Navbar, etc.
│   │   └── organisms/       # DataTable, AppLayout, etc.
│   ├── context/
│   │   └── ThemeContext.jsx # Dark/light mode state
│   ├── Layouts/
│   │   ├── AppLayout.jsx    # Main authenticated layout
│   │   ├── Guest.jsx        # Public pages layout
│   │   └── Authenticated.jsx
│   ├── Pages/               # Full-page components
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   ├── Catalog/
│   │   ├── POS/
│   │   ├── Reports/
│   │   ├── Customers/
│   │   └── Roles/
│   ├── hooks/               # useApi, useAuth, etc.
│   ├── stores/              # Zustand store (auth, UI state)
│   └── utils/               # Helpers, formatters
└── css/
    ├── app.css              # Main stylesheet
    └── theme.css            # CSS variables, light/dark modes
```

## Key Architectural Decisions

### 1. Atomic Design System (ADS)

All UI components follow atomic design:

-   **Atoms**: Primitives (Button, Card, Text, Input, Badge, etc.)
-   **Molecules**: Compositions (FormField, Modal, StatCard, Navbar, etc.)
-   **Organisms**: Complex blocks (DataTable, AppLayout, AppSidebar)
-   **Layouts**: Page templates (AppLayout, Guest, Authenticated)
-   **Pages**: Full experiences (Dashboard, POS, Reports, etc.)

**Benefits**:

-   Consistent, reusable component vocabulary
-   Scalable from small apps to enterprise systems
-   Clear dependency hierarchy (atoms → molecules → organisms)
-   Automated Storybook documentation

### 2. Theme System (CSS Variables)

All colors and design tokens use CSS custom properties:

```css
/* Light mode (default) */
:root,
.light {
    --color-bg-primary: #ffffff;
    --color-text-primary: #111827;
}

/* Dark mode */
.dark {
    --color-bg-primary: #0f172a;
    --color-text-primary: #f1f5f9;
}
```

Components use arbitrary Tailwind values:

```jsx
<div className="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
```

**Benefits**:

-   Single CSS property change toggles dark mode
-   No component re-renders needed
-   Semantic color tokens independent of Tailwind palette
-   Easy to extend with new color schemes

### 3. Domain-Driven Design (Backend)

Business logic organized by domain:

-   `Domain/Catalog/` - Products, types, warehouses
-   `Domain/Inventory/` - Stock management, reorder rules
-   `Domain/POS/` - Carts, items, checkout logic
-   `Domain/Sales/` - Sales, items, fulfillment
-   `Domain/Shared/` - Domain events, exceptions, enums

**Benefits**:

-   Clear separation of concerns
-   Easy to test business rules in isolation
-   Scalable to multiple teams
-   Reduces coupling to framework

### 4. API Versioning

All endpoints under `/api/v1/` with standard response format:

```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

Error responses:

```json
{
    "success": false,
    "error": {
        "code": "validation_failed",
        "message": "Validation failed",
        "details": { "campo": ["mensaje"] }
    }
}
```

**Benefits**:

-   Future-proof for `/api/v2/` without breaking clients
-   Consistent error handling
-   Consistent pagination (page, per_page, total)

### 5. Audit & Observability

-   All sensitive operations logged to `AuditLog` table
-   Request-id correlation for tracing
-   Structured JSON logging to `storage/logs/`
-   Queue jobs for async operations (email, SMS, reports)

## Database Schema (Key Tables)

### User & Auth

-   `users` - Swift Auth users (id, name, email, role)
-   `personal_access_tokens` - API tokens for users

### Inventory Domain

-   `warehouses` - Physical locations
-   `product_types` - Product categories
-   `products` - Catalog items (sku, prices, descriptions)
-   `inventory` - Stock per warehouse
-   `reserved_sku_ranges` - SKU allocation ranges

### Sales Domain

-   `carts` - In-progress sales (seller, warehouse, items)
-   `cart_items` - Line items in carts
-   `sales` - Confirmed transactions (folio, payment method)
-   `sale_items` - Snapshots of products sold
-   `customers` - Customer records with marketing consent

### Operations

-   `audit_logs` - Event trail for sensitive operations
-   `folio_sequences` - Sequential folio generator per warehouse
-   `jobs` - Queue jobs (email, SMS, reports)

## API Architecture

### Request Flow

```
GET /api/v1/products
    ↓
Route::get('/products', [ProductController::class, 'index'])
    ↓
ProductController::index()
    → ProductService::list()
    → ProductRepository::paginate()
    → Eloquent query
    ↓
Response::success($products)
    ↓
JSON { success: true, data: [...] }
```

### Authentication

Bearer token via `equidna/swift-auth`:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Token validated in `AuthMiddleware` → `request->user()` populated.

### Rate Limiting

Configured per route in `config/security.php`:

-   Login: 5 attempts per minute
-   Checkout: 1 per 5 seconds
-   Reports: 10 per minute

## Frontend Architecture

### Routing (Inertia)

Pages automatically mounted to routes via `web.php`:

```php
Route::get('/pos', function () {
    return inertia('POS/Index', ['carts' => $carts]);
});
```

React Page receives props via `useRoute()`:

```jsx
const Dashboard = ({ stats, users }) => {
    return <AppLayout>...</AppLayout>;
};
```

### State Management

-   **Local State**: React `useState()` for UI toggles, forms
-   **Server State**: Inertia props from `useRoute()`
-   **Global Auth**: Zustand store (`useAuthStore()`)
-   **Theme**: React Context (`useTheme()`)

**Pattern**:

```jsx
const { user } = useAuthStore();
const { theme, toggleTheme } = useTheme();
const [isOpen, setIsOpen] = useState(false);
```

### API Integration

Custom hook `useApi()` wraps Inertia router:

```jsx
const { get, post, put, delete: del } = useApi();

const loadProducts = async () => {
    const response = await get("/api/v1/products", {
        query: searchTerm,
    });
    setProducts(response.data);
};
```

Handles:

-   Bearer token injection
-   Error toast notifications
-   Loading state management
-   Request-id correlation

### Component Composition

Example POS page composition:

```jsx
<AppLayout>
    <CartBootstrap warehouses={warehouses} />
    <div className="grid grid-cols-3 gap-4">
        <CartList carts={carts} />
        <CartEditor cart={activeCart} />
        <PaymentForm onCheckout={handleCheckout} />
    </div>
</AppLayout>
```

Each component uses ADS atoms and molecules.

## Build & Deployment

### Development

```bash
# Backend
composer install
php artisan migrate --seed
php artisan serve  # http://localhost:8000

# Frontend
npm install
npm run dev  # http://localhost:5173 (Vite dev server with hot reload)
```

### Production Build

```bash
# Backend
composer install --no-dev
php artisan config:cache
php artisan route:cache
php artisan optimize

# Frontend
npm run build  # → public/build/manifest.json + assets
```

### Environment Configuration

**Backend** (`.env`):

```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
LOG_CHANNEL=stack
```

**Frontend** (automatically via Vite):

-   API_URL injected from Laravel env
-   Theme detection from system preference

### Queue Worker

For async jobs (receipts, reports):

```bash
php artisan queue:work --queue=default,receipts --max-time=3600
```

### Scheduled Tasks

Folio sequence cleanup and old cart archival:

```php
// app/Console/Kernel.php
$schedule->call(function () {
    FolioSequence::cleanup();
    Cart::whereDate('created_at', '<', now()->subDays(30))
        ->where('estado', 'cerrado')
        ->delete();
})->daily();
```

## Monitoring & Troubleshooting

### Logs

-   **Application**: `storage/logs/laravel.log`
-   **Queue**: `storage/logs/queue.log`
-   **Access**: Via Laravel Telescope (optional)

### Common Issues

| Issue                      | Debug                                                                   |
| -------------------------- | ----------------------------------------------------------------------- |
| **Checkout fails**         | Check queue worker running; verify inventory reserves                   |
| **Reports empty**          | Confirm migrations ran, sales exist, dates/filters correct              |
| **Slow product search**    | Check `products(descripcion_corta)` index; profile N+1 queries          |
| **Dark mode not toggling** | Verify ThemeContext provider in app.jsx; check localStorage permissions |

### Performance Tips

1. **API caching**: Use `Cache::remember()` for product lists
2. **N+1 queries**: Use Eloquent `eager()` loading (with, load)
3. **Asset optimization**: Vite code-splitting per page
4. **Database**: Index on `(producto_id, almacen_id)` for inventory queries
5. **Queue**: Use `dispatch(job)->delay(minutes(1))` for batch receipt generation

## Security Best Practices

1. **Input validation**: Strict backend + frontend validation (FormRequest)
2. **Authorization**: Use policies (`@can`, `@cannot`)
3. **Sensitive endpoints**: Rate limit (login, checkout, report export)
4. **Audit trail**: Log user + IP for inventory adjustments, customer changes
5. **Secrets**: Never commit `.env`; use environment variables
6. **CORS**: Configured for trusted domains only
7. **CSRF**: Enabled on web routes; Bearer auth on API routes

## Extending the System

### Adding a New Domain

1. Create `app/Domain/NewDomain/` folder structure
2. Add models to `app/Models/`
3. Create migrations in `database/migrations/`
4. Create API controller in `app/Http/Controllers/API/V1/`
5. Add routes to `routes/api.php`
6. Create React pages in `resources/js/Pages/NewDomain/`

### Adding a New Component

1. Create atom/molecule in `resources/js/components/[atoms|molecules]/`
2. Export from `index.js`
3. Add Storybook story in `.stories.jsx`
4. Test dark mode by toggling theme
5. Document in `docs/DESIGN_SYSTEM.md`

### Adding a New Page

1. Create `resources/js/Pages/[Module]/[Page].jsx`
2. Get data from controller (Inertia props)
3. Call API via `useApi()` for secondary data
4. Compose from ADS atoms/molecules
5. Use `AppLayout` wrapper
6. Add route to `routes/web.php`

## Related Documentation

-   **Design System**: [docs/DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
-   **Requirements**: [docs/REQUIREMENTS.md](./REQUIREMENTS.md)
-   **User Guide**: [docs/USER_GUIDE.md](./USER_GUIDE.md)
-   **Migration Notes**: [docs/MIGRATION.md](./MIGRATION.md)

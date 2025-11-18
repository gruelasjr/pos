# POS Faro

POS Faro es una plataforma punto de venta (POS) web construida con Laravel 12, Inertia.js y React para tiendas que requieren control de inventario multi‑almacén, ventas por mostrador, administración de catálogos, clientes y reportes ejecutivos. El proyecto provee una API REST versionada (`/api/v1`) y una interfaz web responsiva pensada para tablets o escritorios táctiles.

## Tabla de contenidos

1. [Características principales](#características-principales)
2. [Arquitectura](#arquitectura)
3. [Requisitos previos](#requisitos-previos)
4. [Instalación y configuración](#instalación-y-configuración)
5. [Scripts útiles](#scripts-útiles)
6. [Flujos funcionales](#flujos-funcionales)
7. [Estructura de carpetas](#estructura-de-carpetas)
8. [Testing y aseguramiento de calidad](#testing-y-aseguramiento-de-calidad)
9. [Roadmap corto](#roadmap-corto)

## Características principales

-   **Inventario multi‑almacén**: seguimiento de existencias por sucursal con puntos de reorden, bloqueo de SKU reservados y fechas de agotamiento automáticas.
-   **POS con carritos simultáneos**: cada vendedor puede operar múltiples carritos identificados por una clave visual; se soportan descuentos por renglón o totales, pagos mixtos y generación de recibos.
-   **Catálogos completos**: CRUD para almacenes, tipos de producto y productos con búsqueda, filtros e integración futura para captura por cámara.
-   **Clientes y marketing**: registro rápido, opt-in de campañas y ligas de auto-registro desde los recibos.
-   **Reportes operativos**: dashboards diarios/semanales/mensuales, comparativos y ranking por vendedor, con exportaciones y filtros por almacén/tipo de producto.
-   **Observabilidad y seguridad**: logging estructurado JSON con `request-id`, RBAC vía Equidna Swift Auth, tokens Bearer emitidos por el proveedor de tokens del proyecto (Swift Auth o mecanismo equivalente), auditoría de cambios y colas para envíos de recibo.

## Arquitectura

-   **Backend**: Laravel 12 (PHP 8.3), base de datos MySQL 8 (InnoDB, utf8mb4, strict).
-   **Frontend**: Inertia.js + React 18, TailwindCSS 3, HeroUI, Chart.js, Zustand para estado.
-   **Autenticación**: Equidna SwiftAuth gestiona acciones/roles y sesiones; el proyecto usa el proveedor de tokens integrado (Swift Auth) para la emisión/validación de tokens Bearer en el API.
-   **Toolkit de respuestas**: `equidna/toolkit` unifica el formato `{ status, message, data, errors }` configurable según contexto.
-   **Colas y jobs**: receipts enviados mediante jobs asincrónicos (`SendReceiptJob`).
-   **Internacionalización**: ES-MX como idioma predeterminado; copia y UI listas para llaves i18n futuras.

## Requisitos previos

-- PHP 8.3+ con extensiones: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo.

-   Composer 2.5+
-   Node.js 20.19+ (o >=22.12) y npm 10+
-   MySQL 8.x
-   Redis opcional para colas (en local se usa base de datos).

## Instalación y configuración

```bash
git clone <repo> pos-faro
cd pos-faro
composer install
cp .env.example .env
php artisan key:generate

# Configura .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=pos
# DB_USERNAME=pos
# DB_PASSWORD=secret

php artisan migrate --seed
npm install
npm run build    # o npm run dev para entorno local
```

### Configuración adicional

-   `AUTH_GUARD=swift` (u otro guard configurado) protege el API con el guard/token provider configurado.
-   `LOG_STACK=daily` escribe logs JSON estructurados en `storage/logs/laravel.log`.
-   Variables para almacenamiento y notificaciones (`MEDIA_DISK`, `SMS_FROM`, `MAIL_*`) están definidas en `.env.example`. Ajusta según tu infraestructura (S3, proveedor SMTP, gateway SMS real).

## Scripts útiles

| Comando                              | Descripción                                                                          |
| ------------------------------------ | ------------------------------------------------------------------------------------ |
| `composer setup`                     | Instala dependencias PHP, publica `.env`, genera key, migra y ejecuta build front.   |
| `composer dev`                       | Inicia servidor artisan, listener de colas, visor de logs (pail) y Vite en paralelo. |
| `composer test` / `php artisan test` | (Removed) Tests are not included in this workspace.                                  |
| `npm run dev`                        | Vite en modo hot reload.                                                             |
| `npm run build`                      | Genera assets para producción.                                                       |

## Flujos funcionales

### Autenticación / Usuarios

-   `POST /api/v1/auth/login` con email/password devuelve token Bearer.
-   UI: pantalla de login (HeroUI) almacena sesión en localStorage vía Zustand.

### POS

1. Vendedor crea carrito indicando almacén.
2. Añade productos por SKU o búsqueda; se puede editar cantidad/desc descuentos.
3. Aplica descuentos globales y elige método de pago (efectivo, tarjeta, transferencia, mixto con desglose).
4. Realiza checkout (`POST /carts/{id}/checkout`) con transacción que descuenta inventario, genera venta, items y job de recibo.

### Catálogos & Clientes

-   CRUD de almacenes, tipos y productos desde UI (Inertia) con tablas HeroUI.
-   Clientes: listado con búsqueda, registro manual y endpoint `POST /customers/register` percibido desde recibo.

### Reportes

-   Dashboard inicial muestra KPIs diarios, alertas de inventario y ranking de vendedores.
-   Pantallas dedicadas para reportes diarios/semanales/mensuales y por vendedor con gráficas (Chart.js) y tablas exportables.

## Estructura de carpetas

```
app/
 ├─ Domain/         # Servicios de dominio (SKU, Inventario, POS, Sales, Shared)
 ├─ Models/         # Entidades Eloquent (Warehouse, Product, Cart, Sale…)
 ├─ Http/Controllers/API/V1 # Endpoints REST
 ├─ Http/Middleware # Inertia, logging y request context
 ├─ Jobs/           # SendReceiptJob
 ├─ Services/Notifications # Stubs mail/SMS
 └─ Support/        # Helpers (FolioGenerator, AuditLogger, ReceiptRenderer)

packages/
 ├─ equidna/toolkit       # Macros de respuesta + middleware request-id
 └─ equidna/swift-auth    # Guard, tokens y middleware ability

resources/js/
 ├─ Pages/                # Vistas Inertia (Dashboard, POS, Catalog, Reports)
 ├─ Layouts/AppLayout.jsx
 ├─ components/           # Tablas, tarjetitas de stats, etc.
 ├─ hooks/useApi.js       # Wrapper Axios
 ├─ store/authStore.js    # Zustand para token/usuario
 └─ utils/formatters.js
```

## Testing y aseguramiento de calidad

Automated tests and test tooling have been removed from this workspace. Tests may be reintroduced later; consult the project maintainers for the current testing strategy.

Logging JSON + request-id facilita monitoreo en producción; `SendReceiptJob` corre en cola `database` por defecto.

## Roadmap corto

1. **Integraciones reales**: conectar SMTP y proveedor SMS real; mover `Mailer`/`SmsProvider` a drivers configurables.
2. **Puntos/marketing**: implementar página `/r/{token}` para campañas y registro auto gestionado.
3. **Devoluciones y notas de crédito** (v1.1+ según requisitos).
4. **App móvil**: reutilizar API /auth y catálogos para cliente móvil React Native/Flutter.

---

¿Preguntas o sugerencias? Revisa `doc/requirements.md` para el contexto completo y consulta `action_plan.md` para el registro de decisiones y próximos pasos. ¡Buen deploy! 💡

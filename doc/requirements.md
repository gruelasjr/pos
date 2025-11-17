### POS — Especificación Funcional y Técnica (v1.0)

Enciende el faro: construiremos un POS sólido, auditable y extensible. A continuación yacen los requisitos, contratos y reglas que permitirán a un agente de código generar el proyecto de extremo a extremo con mínima ambigüedad.

---

## 1) Alcance y objetivos

-   Punto de venta con inventario, catálogo, ventas en mostrador, clientes y reportes.
-   UI web de escritorio/tablet. Base para futura app móvil consumiendo el mismo API.
-   Multi‑almacén, multi‑vendedor.
-   Seguridad, auditoría de cambios y consistencia transaccional.

## 2) Stack técnico obligatorio

-   Backend: Laravel 12 (PHP 8.3+).
-   Frontend: Inertia.js + React, TailwindCSS. UI kit: HeroUI.
-   DB: MySQL 8.x (InnoDB, utf8mb4, strict mode).
-   Libs internas: equidna/toolkit para manejo de excepciones y respuesta estándar; equdna/swift-auth para autenticación/autorización.
-   API: JSON REST, versionado con prefijo /api/v1, autenticación Bearer (JWT o token de sesión de swift-auth).
-   Observabilidad: logging estructurado (JSON), correlación de request-id, métricas básicas (tiempos, errores, QPS).
-   Internacionalización: ES-MX por defecto; preparar llaves i18n en frontend.

## 3) Dominios y entidades (modelo de datos)

-   Almacen
    -   id (uuid)
    -   nombre (string 120)
    -   codigo (string 32 único)
    -   activo (bool)
    -   creado_en, actualizado_en
-   TipoProducto
    -   id (uuid)
    -   nombre (string 120)
    -   codigo (string 32 único)
    -   creado_en, actualizado_en
-   Producto
    -   id (uuid)
    -   sku (string 64 único) [autogenerado si viene en blanco y NO debe colisionar con rangos reservados]
    -   descripcion_corta (string 160)
    -   descripcion_larga (text nullable)
    -   foto_url (string nullable)
    -   precio_compra (decimal(12,2) >= 0)
    -   precio_venta (decimal(12,2) >= 0)
    -   fecha_ingreso (datetime)
    -   fecha_fin_stock (datetime nullable, autollenado cuando existencias llega a 0)
    -   tipo_producto_id (fk)
    -   activo (bool)
    -   creado_en, actualizado_en
-   Inventario
    -   id (uuid)
    -   producto_id (fk)
    -   almacen_id (fk)
    -   existencias (int >= 0)
    -   punto_reorden (int default 0)
    -   creado_en, actualizado_en
-   RangoSKUReservado
    -   id (uuid)
    -   prefijo (string 16 nullable)
    -   desde (bigint)
    -   hasta (bigint)
    -   usado_hasta (bigint nullable)
    -   proposito (string 120)
    -   creado_en, actualizado_en
-   Carrito (sesiones de venta en curso)
    -   id (uuid)
    -   clave_visual (string 12 para UI rápida)
    -   vendedor_id (fk user)
    -   almacen_id (fk)
    -   estado (enum: activo, en_pausa, cerrado)
    -   total_bruto (decimal(12,2)) [derivado]
    -   descuento_total (decimal(12,2))
    -   total_neto (decimal(12,2)) [derivado]
    -   creado_en, actualizado_en
-   CarritoItem
    -   id (uuid)
    -   carrito_id (fk)
    -   producto_id (fk)
    -   cantidad (int > 0)
    -   precio_unitario (decimal(12,2)) [copiado de producto.precio_venta al momento]
    -   descuento (decimal(12,2) por ítem)
    -   subtotal (decimal(12,2)) [derivado]
-   Venta
    -   id (uuid)
    -   folio (string 32 único, secuencial por almacén)
    -   almacen_id (fk)
    -   vendedor_id (fk user)
    -   cliente_id (fk nullable)
    -   metodo_pago (enum: efectivo, tarjeta, transferencia, mixto)
    -   total_bruto, descuento_total, total_neto (decimal(12,2))
    -   pagado_en (datetime)
    -   creado_en, actualizado_en
-   VentaItem
    -   id (uuid)
    -   venta_id (fk)
    -   producto_id (fk)
    -   sku (string 64 snapshot)
    -   descripcion (string 160 snapshot)
    -   cantidad (int > 0)
    -   precio_unitario (decimal(12,2))
    -   descuento (decimal(12,2))
    -   subtotal (decimal(12,2))
-   Cliente
    -   id (uuid)
    -   nombre (string 160)
    -   email (string 160 nullable)
    -   telefono (string 32 nullable)
    -   acepta_marketing (bool)
    -   creado_en, actualizado_en
-   Usuario (swift-auth)
    -   id, nombre, email, rol (admin, vendedor, auditor)

Indices clave: únicos en sku, folio; compuestos en inventario (producto_id, almacen_id); búsqueda por descripcion_corta.

## 4) Reglas de negocio

-   SKU
    -   Generación: si no se proporciona, asignar automáticamente.
    -   No colisionar con rangos en RangoSKUReservado. Un servicio genera N SKUs y avanza usado_hasta.
-   Inventario
    -   Venta descuenta existencias en Inventario por almacen.
    -   Si existencias llega a 0 tras confirmar venta, setear fecha_fin_stock del Producto si está vacío.
    -   Transacciones atómicas: confirmación de venta y descuento de inventario ocurren en la misma transacción DB.
-   Descuentos
    -   A nivel ítem y a nivel carrito/venta. No se permiten totales negativos.
-   Carritos simultáneos
    -   Cada vendedor puede tener múltiples carritos con clave_visual para alternar rápidamente.
-   Métodos de pago
    -   efectivo, tarjeta, transferencia, mixto. Para mixto se requieren montos por componente.
-   Recibos
    -   Generación PDF/HTML, envío por email o SMS (enviar vía adaptadores, simular si no hay gateway real).
    -   Incluir liga de registro del cliente para puntos/notificaciones: /r/{token}.
-   Auditoría
    -   Log de eventos: creación/edición producto, cambios de inventario, confirmación/cancelación de venta.
-   Permisos (RBAC)
    -   admin: total
    -   vendedor: POS, clientes, lectura de catálogo
    -   auditor: lectura de todo, sin mutaciones

## 5) API pública (REST /api/v1)

Autenticación: Authorization: Bearer <token>.

Respuestas: { success: bool, data, error: {code, message, details?} } por equidna/toolkit.

Paginación: page, per_page, total.

-   Auth
    -   POST /auth/login {email, password} -> {token}
-   Almacenes
    -   GET /warehouses
    -   POST /warehouses
    -   PATCH /warehouses/{id}
-   Tipos de producto
    -   GET /product-types, POST, PATCH
-   Productos
    -   GET /products?query=&tipo_id=&almacen_id=
    -   POST /products { sku?, descripcion_corta, descripcion_larga?, foto_url?, precio_compra, precio_venta, fecha_ingreso, tipo_producto_id, activo }
    -   PATCH /products/{id}
    -   GET /products/{id}
-   Inventario
    -   GET /inventory?almacen_id=&producto_id=
    -   PATCH /inventory/adjust { producto_id, almacen_id, delta, motivo }
-   SKU generator
    -   POST /skus/reserve { cantidad, prefijo? } -> { skus: ["..."], rango_id }
-   Carritos (POS)
    -   GET /carts?estado=activo
    -   POST /carts { almacen_id } -> {id, clave_visual}
    -   POST /carts/{id}/items { producto_id, cantidad, precio_unitario?, descuento? }
    -   PATCH /carts/{id}/items/{item_id} { cantidad?, descuento? }
    -   DELETE /carts/{id}/items/{item_id}
    -   PATCH /carts/{id} { descuento_total?, estado? }
    -   POST /carts/{id}/checkout { metodo_pago, pagos_detalle?, cliente_id? } -> crea Venta, descuenta inventario, cierra carrito
-   Ventas
    -   GET /sales?desde=&hasta=&almacen_id=&vendedor_id=
    -   GET /sales/{id}
    -   POST /sales/{id}/receipt { canal: email|sms, destino }
-   Clientes
    -   GET /customers?query=
    -   POST /customers { nombre, email?, telefono?, acepta_marketing }
    -   PATCH /customers/{id}
    -   POST /customers/register { token, ... } // desde la liga del recibo
-   Reportes
    -   GET /reports/daily?fecha=&almacen_id=&tipo_id=
    -   GET /reports/weekly?semana=&comparar=1
    -   GET /reports/monthly?mes=&comparar=1
    -   GET /reports/by-seller?desde=&hasta=&almacen_id=

## 6) UI y flujos clave (Inertia + React)

-   Login
    -   Form email/password. Manejo de errores estándar.
-   Dashboard
    -   Vistas rápidas: ventas hoy, alerta de bajo inventario, accesos a reportes.
-   Catálogos
    -   Almacenes, Tipos de producto, Productos: CRUD con búsqueda, filtros, subida de imagen por cámara o librería.
    -   Alta de producto: permite escanear SKU desde cámara; si vacío, autogenerar.
-   POS (Caja)
    -   Lista de carritos activos con clave_visual.
    -   Agregar productos escaneando código de barras (lector o cámara) o búsqueda por descripción.
    -   Editar cantidad, eliminar renglón, aplicar descuento por renglón y total.
    -   Mostrar totales en tiempo real, impuestos futuros opcionales.
    -   Seleccionar método de pago. Si mixto, capturar desglose. Confirmar.
    -   Enviar recibo por email o SMS tras confirmar.
-   Reportes
    -   Diarios, semanales (comparativos), mensuales (comparativos), por vendedor.
    -   Filtros por almacén y tipo de producto. Exportar CSV.
-   Clientes
    -   Búsqueda rápida. Alta sencilla. Marca acepta_marketing.

## 7) Integraciones y adaptadores

-   Email: interfaz Mailer con implementación SMTP local y stub.
-   SMS: interfaz SmsProvider con stub por defecto.
-   Almacenamiento de imágenes: disco local en desarrollo, S3 compatible en producción.

## 8) Seguridad y cumplimiento

-   Validaciones estrictas backend y frontend.
-   Sanitización de entrada, límites de tasa en endpoints sensibles.
-   CSRF en rutas web; CORS configurado para futuras apps.
-   Registros de auditoría con usuario, ip, payload mínimo no sensible.

## 9) Rendimiento y escalabilidad

-   Índices en búsquedas principales.
-   Uso de jobs en cola para envío de recibos.
-   Paginación en listados.

## 10) Migraciones y semillas

-   Migraciones para todas las entidades.
-   Seeders: roles, usuario admin, almacén principal, tipos de producto demo.

## 11) Pruebas y calidad

-   Pruebas unitarias de dominios críticos: generación de SKU, checkout, descuentos, inventario.
-   Pruebas de API con Pest o PHPUnit.
-   CI: lint, tests, build.

## 12) Aceptación (criterios verificables)

-   Crear N SKUs reservados y autogenerar SKU de producto sin colisionar.
-   Crear producto, subir foto desde cámara y desde librería.
-   Crear dos carritos, alternar entre ellos, aplicar descuentos y confirmar venta con método mixto.
-   Confirmar venta descuenta inventario del almacén correcto y setea fecha_fin_stock al llegar a 0.
-   Enviar recibo por email y por SMS exitosamente usando stubs.
-   Reportes muestran totales del día, semana con comparativo, mes con comparativo, y por vendedor con filtros.

## 13) No objetivos (out of scope v1)

-   Facturación fiscal.
-   Impuestos complejos multi‑tasa.
-   Devoluciones y cambios.
-   Integraciones con terminal bancaria.

## 14) Roadmap breve (v1.1+)

-   Devoluciones con notas de crédito.
-   Programa de puntos y notificaciones transaccionales reales.
-   App móvil con el mismo API.

---

### Notas de implementación

-   Usar transacciones DB en /carts/{id}/checkout.
-   Normalizar precios con Money objects o decimales fijos. Evitar floats.
-   Consistencia de respuesta vía equidna/toolkit.
-   Centralizar generación de folios por almacén con tabla secuenciadora.

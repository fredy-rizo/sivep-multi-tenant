# Núcleo de granja — inventario, clientes y ventas

Módulo comercial del SIVEP 2.0. Todas las tablas incluyen `organization_id` y
usan el trait `BelongsToOrganization`, por lo que el aislamiento multi-tenant es
automático. Las rutas se protegen con `auth:sanctum`, `organization`,
`feature:<código>` y `permission:<permiso>`.

## Tablas

### `products`
Catálogo de productos vendibles o consumibles.

| Campo | Descripción |
| --- | --- |
| `sku` | Código único por organización |
| `name`, `category`, `unit` | Nombre, categoría y unidad de medida |
| `price`, `cost` | Precio de venta y costo de referencia |
| `stock`, `min_stock` | Existencia actual y mínimo para alertas |
| `is_active` | Baja lógica |

### `inventory_movements`
Trazabilidad de cada cambio de stock.

| Campo | Descripción |
| --- | --- |
| `type` | `entrada`, `salida` o `ajuste` |
| `quantity` | Firmada: positiva en entradas, negativa en salidas |
| `stock_before`, `stock_after` | Foto del stock antes y después |
| `unit_cost` | Costo de la entrada (opcional) |
| `sale_id` | Venta asociada cuando aplica |
| `reason`, `movement_date`, `created_by` | Motivo, fecha y usuario |

El stock se actualiza dentro de una transacción con `lockForUpdate`. Una salida
que dejaría stock negativo devuelve **422**.

### `customers`
Directorio de compradores. `document_number` es único por organización.
Guarda tipo de documento, contacto, dirección, ciudad, notas y estado.

### `sales`
Cabecera de la venta: código único por organización (`VTA-YYYYMMDD-NNNN`),
cliente opcional, fecha, estado (`pagada`, `pendiente`, `anulada`), método de
pago, subtotal, descuento, impuestos, total y datos de anulación.

### `sale_items`
Líneas de la venta. Cada línea referencia un `product_id` **o** un `animal_id`,
nunca ambos. La cantidad, precio unitario y total de línea se guardan como
snapshot.

## Reglas de negocio

1. **Venta de producto**: descuenta stock y crea un movimiento de salida.
2. **Venta de animal**: marca el animal como `vendido` y registra el evento
   `venta` en su historial.
3. **Anulación**: devuelve el stock de productos, reactiva animales y deja la
   venta en estado `anulada` con fecha y motivo. No se puede anular dos veces.
4. **Totales**: `total = subtotal - descuento + impuestos`; el descuento no puede
   superar el subtotal.
5. **Stock inicial**: al crear un producto con stock se registra una entrada
   `Stock inicial`, nunca se escribe el stock directamente.

## Pedidos públicos

El portal público permite que un cliente sin sesión arme un pedido desde
`/catalogo/:slug` y adjunte su comprobante. Los pedidos **no descuentan stock**
al recibirse: se descuentan al aprobarlos, cuando se convierten en venta.

- `public_orders`: código por organización (`PED-YYYYMMDD-NNNN`), datos del
  cliente, medio de pago, estado (`pendiente`, `aprobado`, `rechazado`), total,
  comprobante privado y trazabilidad de revisión.
- `public_order_items`: snapshot de producto, cantidad, precio unitario y total
  de línea.
- El comprobante se guarda en `storage/app/private` y solo se descarga desde la
  API autenticada.
- Aprobar crea o reutiliza el cliente por documento y genera la venta con
  `SaleService` (descuento de inventario incluido).
- Rechazar exige un motivo y no altera el inventario.
- Requiere la feature `public.form` (plan Avanzado), permisos `orders.view` y
  `orders.manage`, y limita la creación pública a 8 pedidos por minuto por IP y
  organización.

Ver [`PORTAL-PUBLICO.md`](PORTAL-PUBLICO.md) para el detalle funcional.

## Endpoints

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET/POST | `/api/v1/products` | `inventory.view` / `inventory.manage` |
| GET/PUT/DELETE | `/api/v1/products/{id}` | `inventory.view` / `inventory.manage` |
| GET/POST | `/api/v1/inventory/movements` | `inventory.view` / `inventory.manage` |
| GET | `/api/v1/inventory/summary` | `inventory.view` |
| GET/POST | `/api/v1/customers` | `customers.view` / `customers.manage` |
| GET/PUT/DELETE | `/api/v1/customers/{id}` | `customers.view` / `customers.manage` |
| GET | `/api/v1/customers/summary` | `customers.view` |
| GET/POST | `/api/v1/sales` | `sales.view` / `sales.create` |
| GET | `/api/v1/sales/{id}` | `sales.view` |
| POST | `/api/v1/sales/{id}/cancel` | `sales.cancel` |
| GET | `/api/v1/reports/farm` | `reports.view` |

## Features por plan

| Feature | Básico | Intermedio | Avanzado |
| --- | --- | --- | --- |
| `inventory.core` | — | — | ✅ |
| `clients.core` | — | — | ✅ |
| `sales.core` | — | — | ✅ |
| `public.form` | — | — | ✅ |

Si el plan no incluye la feature, la API responde **402** con
`code: FEATURE_NOT_INCLUDED`.

## Pruebas

`api/tests/Feature/FarmApiTest.php` cubre:

- listado y resumen de inventario, clientes y ventas;
- stock inicial con movimiento de entrada;
- descuento y restauración de stock al vender y anular;
- venta de animal con cambio de estado y anulación;
- gating del plan básico;
- permisos de operario (vende, no anula);
- aislamiento de productos entre organizaciones.

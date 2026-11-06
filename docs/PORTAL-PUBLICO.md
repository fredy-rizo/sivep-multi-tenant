# Portal público de pedidos

Catálogo en línea por organización para recibir pedidos con comprobante de pago.
Disponible en el plan **Avanzado** con la feature `public.form`.

## Flujo

1. La finca comparte el enlace `/catalogo/{slug}` (por ejemplo
   `/catalogo/finca-la-esperanza`).
2. El cliente elige productos y cantidades. El catálogo solo muestra productos
   activos y su disponibilidad.
3. El cliente registra sus datos, elige el medio de pago y adjunta una imagen o
   PDF de máximo 10 MB.
4. El pedido queda **pendiente** con código `PED-YYYYMMDD-NNNN`. No se descuenta
   stock todavía.
5. La finca revisa el comprobante en `/pedidos`:
   - **Aprobar**: crea o reutiliza el cliente por documento y registra la venta.
     El inventario se descuenta en ese momento.
   - **Rechazar**: pide un motivo y no toca inventario ni ventas.
6. El comprobante se descarga desde la API autenticada; nunca se expone como
   archivo público.

## Seguridad

- Rutas públicas sin sesión resueltas por `slug` de una organización activa.
- Gating por `public.form`: si el plan no la incluye, responde **402** con
  `code: FEATURE_NOT_INCLUDED`.
- Rate limit de 8 pedidos por minuto por IP + organización.
- Comprobantes en `storage/app/private/public-orders/{organization_id}`.
- Validación de MIME real, tamaño y extensión (`jpg`, `jpeg`, `png`, `webp`,
  `pdf`).
- Bandeja protegida por `orders.view`; aprobación/rechazo por `orders.manage`.
- El aislamiento multi-tenant impide ver o revisar pedidos de otra organización
  (responde **404**).

## Endpoints públicos

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/api/v1/public/{slug}/catalog` | Organización, productos y medios de pago |
| POST | `/api/v1/public/{slug}/orders` | Crear pedido multipart con `receipt` |

## Endpoints autenticados

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/api/v1/public-orders` | `orders.view` |
| GET | `/api/v1/public-orders/summary` | `orders.view` |
| GET | `/api/v1/public-orders/{id}` | `orders.view` |
| GET | `/api/v1/public-orders/{id}/receipt` | `orders.view` |
| POST | `/api/v1/public-orders/{id}/approve` | `orders.manage` |
| POST | `/api/v1/public-orders/{id}/reject` | `orders.manage` |

## Roles

| Rol | Ver pedidos | Aprobar / rechazar |
| --- | --- | --- |
| `super_admin` | ✅ | ✅ |
| `admin_granja` | ✅ | ✅ |
| `veterinario` | ✅ | — |
| `operario` | — | — |

## Interfaz

- `/catalogo/:slug`: página pública, sin layout administrativo ni sesión.
- `/pedidos`: bandeja con indicadores, filtros, detalle, descarga de comprobante,
  aprobación y rechazo.
- El enlace público se copia desde la bandeja y solo aparece si el plan incluye
  `public.form`.

## Pruebas

`api/tests/Feature/PublicOrderApiTest.php` cubre:

- catálogo público y cálculo del total;
- almacenamiento del comprobante;
- gating del plan sin `public.form`;
- aprobación con creación de cliente, venta y descuento de stock;
- rechazo con motivo y sin venta;
- bloqueo de doble aprobación;
- aislamiento de pedidos entre organizaciones;
- permisos de operario y veterinario;
- rechazo de cantidades superiores al stock.

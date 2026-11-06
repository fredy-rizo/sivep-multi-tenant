# Arquitectura — SIVEP 2.0

Documento de referencia de la reescritura. Resume decisiones, estructura,
convenciones y fases. El código anterior vive en `legacy/` solo como referencia
funcional; no se reutiliza.

---

## 1. Decisiones cerradas

| Tema | Decisión |
| --- | --- |
| Backend | PHP 8.3+ · Laravel (última estable, 12+) · API REST |
| Frontend | Vue 3 · Vite · Pinia · Vue Router · Tailwind |
| Base de datos | MySQL 8 |
| Multi-tenant | Una base de datos compartida con `organization_id` |
| Autenticación | Laravel Sanctum SPA (cookie HttpOnly + CSRF) |
| Autorización | `spatie/laravel-permission` + gating por plan |
| Facturación | Pago manual, con interfaz `PaymentGateway` extensible |
| Ganado | Multiespecie configurable (especies, razas y tipos de evento) |
| Datos históricos | No se migran; base nueva con seeds de demostración |
| Realtime | Laravel Reverb (WebSocket) o SSE si se evita Redis |
| Pruebas | Pest |
| Estilo de código | Laravel Pint + ESLint/Prettier |
| Documentación API | Scramble o Scribe |

---

## 2. Estructura del repositorio

```
sivep-multi-tenant/
├── api/                  # Laravel (se crea con Composer)
├── web/                  # Vue 3 + Vite (ya inicializado)
├── legacy/
│   ├── backend/          # Node + Fastify anterior (referencia)
│   └── frontend/         # React anterior (referencia)
├── docs/
│   ├── ARQUITECTURA.md
│   └── MODELO-GANADERO.md
├── .editorconfig
├── .gitignore
└── README.md
```

### Crear el backend cuando PHP y Composer estén disponibles

```bash
composer create-project laravel/laravel api
cd api
composer require laravel/sanctum spatie/laravel-permission
composer require --dev pestphp/pest laravel/pint
php artisan install:api
```

> Laravel 12+ incluye `install:api` para publicar Sanctum y las rutas de API.

---

## 3. Backend Laravel

### 3.1 Capas

```
app/
├── Domain/                     # Reglas de negocio puras (opcional)
│   ├── Billing/
│   └── Livestock/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   │   ├── ResolveOrganization.php
│   │   ├── EnsureFeature.php
│   │   └── EnsureActiveSubscription.php
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Services/
│   ├── Billing/
│   │   ├── PaymentGateway.php
│   │   ├── ManualGateway.php
│   │   └── SubscriptionService.php
│   └── Livestock/
│       ├── AnimalEventService.php
│       └── MonthlySummaryService.php
├── Jobs/
├── Console/Commands/
└── Support/
    └── TenantContext.php
```

Reglas:

- Controladores delgados; la lógica vive en servicios y acciones.
- Validación siempre con Form Requests.
- Respuestas con API Resources (nunca modelos crudos).
- Errores de negocio con excepciones propias y códigos HTTP correctos.
- Nada de consultas Eloquent sin scope de organización en rutas privadas.

### 3.2 Multi-tenant

**Estrategia:** una sola base de datos, aislamiento lógico por `organization_id`.

Componentes:

1. `TenantContext` (singleton): organización resuelta para la petición.
2. `ResolveOrganization` (middleware): se ejecuta después de `auth:sanctum`.
   - `admin_granja`, `veterinario`, `operario`: organización del usuario, sin excepción.
   - `super_admin`: puede enviar `X-Organization-Id` para soporte; si no lo envía,
     solo accede a rutas de administración de plataforma.
   - Rutas públicas: resuelven la organización por dominio, slug o la organización
     pública por defecto.
3. Trait `BelongsToOrganization`:
   - `bootBelongsToOrganization()` agrega `addGlobalScope('organization', ...)`.
   - `creating` rellena `organization_id` desde `TenantContext` si está vacío.
   - Nunca se acepta `organization_id` desde el request del cliente.
4. `Organization` es el modelo raíz. Las tablas de negocio tienen FK con
   `ON DELETE CASCADE` salvo catálogos globales.

**Regla de oro:** toda consulta de negocio debe pasar por el trait o filtrar
explícitamente por `organization_id`. Las pruebas de aislamiento son obligatorias.

### 3.3 Autenticación

Sanctum SPA:

- `GET /sanctum/csrf-cookie` antes de login.
- `POST /api/auth/login` → valida credenciales, regenera sesión y devuelve usuario.
- `GET /api/auth/me` → usuario + organización + roles + permisos.
- `POST /api/auth/logout` → invalida sesión.
- Rate limit específico en login (p. ej. 5 intentos/min por IP + email).
- Cookies: `HttpOnly`, `Secure` en producción, `SameSite=Lax`.
- Sin tokens en `localStorage`.

### 3.4 Roles y permisos

Roles base:

| Rol | Alcance |
| --- | --- |
| `super_admin` | Operador de la plataforma. Administra organizaciones, planes, pagos y licencias. |
| `admin_granja` | Administra su organización: usuarios, operación y facturación. |
| `veterinario` | Ganado, eventos sanitarios, vacunas y reportes clínicos. |
| `operario` | Registro de pesos, movimientos y tareas operativas asignadas. |

Permisos sugeridos (se agrupan por módulo):

```
organization.view, organization.manage
users.view, users.manage
animals.view, animals.create, animals.update, animals.delete
events.view, events.create, events.update
weights.create
vaccines.view, vaccines.manage
inventory.view, inventory.manage
sales.view, sales.create, sales.cancel
customers.view, customers.manage
reports.basic, reports.advanced
billing.view, billing.manage
platform.manage
```

La autorización se aplica en tres niveles:

1. Rol/permiso (`permission:` middleware o `$this->authorize`).
2. Pertenencia a la organización (global scope + policy).
3. Feature del plan (`EnsureFeature`).

### 3.5 Planes y suscripciones

Tablas:

- `plans`, `features`, `plan_feature`
- `subscriptions`, `payments`, `coupons`, `coupon_redemptions`
- `licenses`, `services`, `service_orders`

Flujo de pago manual:

1. `admin_granja` solicita cambio de plan → `payments.status = pendiente`.
2. `super_admin` confirma o rechaza el pago.
3. Al confirmar: se cancela la suscripción anterior y se activa la nueva.
4. Un comando programado expira suscripciones vencidas y aplica cancelaciones.

Abstracción de pasarela:

```php
interface PaymentGateway
{
    public function charge(Payment $payment): PaymentResult;
    public function handleWebhook(Request $request): void;
}
```

`ManualGateway` es la implementación inicial. Wompi/MercadoPago/Stripe se agregan
después sin tocar los controladores.

Gating:

- `EnsureFeature::class.':analytics.algebra'`
- Responde `402 Payment Required` con `code: FEATURE_NOT_INCLUDED`.
- `super_admin` siempre pasa.
- El catálogo de features se cachea y se invalida al editar planes.

Features nuevas para ganado:

| Código | Descripción | Plan mínimo |
| --- | --- | --- |
| `livestock.core` | Animales, eventos, pesos y vacunas | Básico |
| `livestock.reports` | Reportes mensuales y sanitarios | Profesional |
| `livestock.analytics` | GMD, proyecciones y alertas | Profesional |
| `livestock.multi_species` | Catálogo multiespecie configurable | Institucional |

### 3.6 Tiempo real

- Laravel Reverb publica eventos en canales privados por organización:
  `private-organization.{id}.stock`, `private-organization.{id}.livestock`.
- El cliente se suscribe con Laravel Echo; el backend autoriza el canal contra
  la organización del usuario.
- Si no se quiere operar Redis/Reverb en la primera versión, usar SSE con un
  endpoint autenticado y polling de respaldo.

### 3.7 Tareas programadas

`routes/console.php` / `app/Console/Kernel.php`:

- Expirar suscripciones y cancelaciones programadas (cada hora).
- Vencer pedidos en efectivo y devolver stock (cada hora).
- Recalcular resúmenes mensuales de ganado (diario).
- Generar alertas de vacunas próximas (diario).
- Limpiar sesiones y cachés.

---

## 4. Frontend Vue

### 4.1 Estructura actual

```
web/src/
├── api/http.js          # Axios + CSRF de Sanctum
├── components/          # Layout, sidebar, header, placeholders
├── data/                # Datos demo (se eliminarán)
├── layouts/AppLayout.vue
├── pages/
│   ├── animals/         # Inventario y ficha animal
│   ├── LoginPage.vue
│   ├── DashboardPage.vue
│   ├── PlansPage.vue
│   └── ...
├── router/index.js      # Rutas + guards
├── stores/              # Pinia: auth, plan
└── style.css            # Tailwind + tema UNAL
```

### 4.2 Convenciones

- Componentes en PascalCase; composables en `useXxx`.
- Stores Pinia por dominio: `auth`, `plan`, `animals`, `inventory`, `sales`.
- Llamadas HTTP solo desde stores o servicios, no desde componentes complejos.
- Guard de rutas:
  - `requiresAuth`
  - `roles`
  - `feature` (se validará contra el plan)
- `v-can` o componente `<Can feature="...">` para ocultar acciones premium.
- El backend siempre revalida; el frontend solo mejora la UX.
- Tailwind con tokens UNAL definidos en `style.css`.
- Formularios con validación local + errores 422 del backend.

### 4.3 Módulos de la SPA

| Ruta | Módulo | Estado |
| --- | --- | --- |
| `/login` | Autenticación | UI lista |
| `/dashboard` | Resumen operativo | UI base |
| `/ganado` | Inventario animal | UI demo |
| `/ganado/:id` | Ficha del animal | UI demo |
| `/inventario` | Stock y movimientos | UI conectada |
| `/ventas` | Ventas | UI conectada |
| `/clientes` | Clientes | UI conectada |
| `/pedidos` | Pedidos públicos y comprobantes | UI conectada |
| `/catalogo/:slug` | Catálogo público sin sesión | UI conectada |
| `/reportes` | Reportes y analítica | UI conectada |
| `/mi-plan` | Suscripción | UI base |
| `/admin/monetizacion` | Back office SaaS | Placeholder |
| `/usuarios` | Usuarios y roles | UI conectada |
| `/planes` | Catálogo público | UI lista |

---

## 5. Seguridad

- Sanctum SPA + CSRF; sin tokens en almacenamiento del navegador.
- Rate limit global y específico en login, formularios públicos y subida de archivos.
- Validación estricta de archivos (MIME real, tamaño, extensión).
- Cloudinary o disco privado para comprobantes; URLs firmadas cuando aplique.
- CORS restringido por entorno.
- HTTPS obligatorio en producción.
- Secretos solo en `.env`; nunca en el repositorio.
- Auditoría de acciones sensibles (`activity_log` o tabla propia).
- Políticas de aislamiento multi-tenant probadas con Pest.

---

## 6. Fases

| Fase | Entregable |
| --- | --- |
| 0 | Repositorio, `.gitignore`, legado preservado, documentación, frontend base |
| 1 | Laravel + Sanctum + organizaciones + usuarios + roles + multi-tenant |
| 2 | Módulo ganadero: especies, razas, lotes, animales, eventos, pesos, vacunas |
| 3 | Núcleo de granja: productos, inventario, clientes, ventas, reportes |
| 4 | Suscripciones, planes, pagos manuales, cupones y gating |
| 5 | Portal público, formularios y archivos |
| 6 | Tiempo real, analítica avanzada y reportes ganaderos |
| 7 | Endurecimiento, pruebas E2E, seeds, documentación y despliegue |

---

## 7. Requisitos de entorno

- PHP 8.3+ con extensiones: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`.
- Composer 2.
- MySQL 8 (o SQLite para desarrollo).
- Node 20+ y pnpm.
- Redis (recomendado para colas, caché y Reverb).
- Nginx + PHP-FPM en producción; Supervisor para colas y Reverb.

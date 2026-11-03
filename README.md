# SIVEP 2.0

Sistema de inventario, ventas, producción y gestión ganadera multi-organización
para la Granja El Cairo (Universidad Nacional de Colombia — Sede Orinoquía).

Esta es una **reescritura completa** del sistema anterior:

- **Backend:** PHP 8.5 + Laravel 13 (API REST).
- **Frontend:** Vue 3 + Vite + Pinia + Tailwind 4.
- **Multi-tenant:** una base de datos con aislamiento por `organization_id`.
- **Suscripciones:** planes, features, gating y pasarela de pago extensible.
- **Módulo ganadero:** multiespecie, ID único por animal, pesos, vacunas,
  eventos y resúmenes mensuales.

El código anterior (Node/Fastify + React) se conserva en `legacy/` únicamente
como referencia funcional.

---

## Estado actual

| Componente | Estado |
| --- | --- |
| Repositorio y control de versiones | ✅ |
| Preservación del legado | ✅ `legacy/` |
| Backend Laravel + Sanctum + permisos | ✅ |
| Multi-tenant (`organization_id`, scope y middleware) | ✅ |
| Módulo ganadero (API y servicios) | ✅ |
| Planes, suscripciones y gating por feature | ✅ |
| Frontend Vue conectado al API | ✅ |
| Base de datos local | ⏳ Crear usuario/BD MySQL |
| Inventario, ventas, clientes y reportes avanzados | ✅ |
| Tests automatizados | ✅ |

---

## Estructura

```
sivep-multi-tenant/
├── api/                  # Laravel 13 (API REST)
│   ├── app/
│   │   ├── Contracts/    # PaymentGateway
│   │   ├── Http/         # Controladores, requests, resources, middleware
│   │   ├── Models/       # Dominio + trait BelongsToOrganization
│   │   ├── Services/     # Ganadería y billing
│   │   └── Support/      # TenantContext
│   ├── database/         # Migraciones y seeders
│   └── routes/api.php
├── web/                  # Vue 3 + Vite
├── legacy/
│   ├── backend/          # Backend Node/Fastify anterior
│   └── frontend/         # Frontend React anterior
├── docs/
│   ├── ARQUITECTURA.md
│   └── MODELO-GANADERO.md
└── README.md
```

---

## Requisitos

- PHP 8.3+ con `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`.
- Composer 2.
- MySQL 8.
- Node 20+ y pnpm.

---

## Puesta en marcha

### 1. Crear la base de datos

Las credenciales reales se configuran en `api/.env` (no se versiona). En este
equipo la configuración actual es:

- host `localhost:3306`
- base de datos `sivep-multi-tennant`
- usuario `fredy`
- contraseña propia del usuario MySQL `fredy`
- socket `/opt/lampp/var/mysql/mysql.sock`

> La base nueva se llama `sivep-multi-tennant` para no mezclarla con la base
> `sivep` antigua que ya existía en el equipo. Si prefieres otro nombre,
> cámbialo en `api/.env` y en los comandos siguientes.

Crea la base y las pruebas con una cuenta con privilegios (por ejemplo el root
de MySQL):

```bash
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`sivep-multi-tennant\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS sivep_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`sivep-multi-tennant\`.* TO 'fredy'@'localhost';
GRANT ALL PRIVILEGES ON sivep_test.* TO 'fredy'@'localhost';
FLUSH PRIVILEGES;"
```

> **Importante en XAMPP/LAMPP:** con `DB_HOST=localhost` PHP busca el socket del
> sistema (`/var/run/mysqld/mysqld.sock`), pero XAMPP usa
> `/opt/lampp/var/mysql/mysql.sock`. Por eso `api/.env` incluye
> `DB_SOCKET=/opt/lampp/var/mysql/mysql.sock`. La alternativa es usar
> `DB_HOST=127.0.0.1`.

`sivep_test` se usa para las pruebas automatizadas (`php artisan test`). Sus
credenciales se configuran en `api/phpunit.xml`.

> Si tu MySQL usa otro puerto o credenciales, ajusta `DB_PORT`, `DB_USERNAME` y
> `DB_PASSWORD` en `api/.env`.

### 2. Backend

```bash
cd api
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

Si necesitas reinstalar dependencias desde cero:

```bash
composer install
php artisan key:generate
```

### 3. Frontend

```bash
cd web
pnpm install
pnpm dev   # http://localhost:5173
```

Vite hace proxy de `/api`, `/sanctum` y `/storage` hacia `http://localhost:8000`.

---

## Usuarios de demostración

Los seeders crean una organización demo (`Finca La Esperanza`) con datos
ganaderos multiespecie.

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Super administrador | `admin@sivep.local` | `Admin123*` |
| Administrador de granja | `dueno@finca.local` | `Demo123*` |
| Veterinario | `veterinario@finca.local` | `Demo123*` |
| Operario | `operario@finca.local` | `Demo123*` |

> El `super_admin` no pertenece a una organización; para usar rutas de negocio
> debe enviar `X-Organization-Id`. El frontend de soporte para ese selector es
> una fase posterior.

---

## API v1

Autenticación por sesión (Sanctum SPA, cookies HttpOnly + CSRF).

| Método | Ruta | Descripción |
| --- | --- | --- |
| POST | `/api/v1/auth/login` | Iniciar sesión |
| GET | `/api/v1/auth/me` | Usuario autenticado |
| POST | `/api/v1/auth/logout` | Cerrar sesión |
| GET | `/api/v1/plans` | Planes y features (público) |
| GET | `/api/v1/billing/current` | Suscripción actual |
| POST | `/api/v1/billing/change-plan` | Cambiar de plan |
| POST | `/api/v1/billing/cancel` | Cancelar al final del periodo |
| GET/POST | `/api/v1/animals` | Listar / crear animales |
| GET/PUT/DELETE | `/api/v1/animals/{id}` | Detalle, edición y baja lógica |
| POST | `/api/v1/animals/{id}/events` | Registrar peso, vacuna o evento |
| GET | `/api/v1/events` | Listar eventos con filtros |
| GET | `/api/v1/catalogs/*` | Especies, razas, lotes, vacunas y tipos |
| GET/POST | `/api/v1/products` | Listar / crear productos |
| GET/PUT/DELETE | `/api/v1/products/{id}` | Detalle, edición y baja lógica |
| GET/POST | `/api/v1/inventory/movements` | Movimientos de stock |
| GET | `/api/v1/inventory/summary` | Resumen y alertas de stock |
| GET/POST | `/api/v1/customers` | Listar / crear clientes |
| GET/PUT/DELETE | `/api/v1/customers/{id}` | Detalle, edición y baja lógica |
| GET | `/api/v1/customers/summary` | Indicadores de clientes |
| GET/POST | `/api/v1/sales` | Listar / registrar ventas |
| GET | `/api/v1/sales/{id}` | Detalle de una venta |
| POST | `/api/v1/sales/{id}/cancel` | Anular y restaurar stock |
| GET | `/api/v1/reports/*` | Reportes ganaderos y comerciales |

El gating por plan responde **402** cuando falta una feature y los permisos
responden **403**.

---

## Decisiones de arquitectura

| Tema | Decisión |
| --- | --- |
| Multi-tenant | Una BD con `organization_id` + global scope |
| Autenticación | Sanctum SPA (cookies HttpOnly + CSRF) |
| Autorización | Roles/permisos + gating por plan |
| Facturación | Pago manual con interfaz `PaymentGateway` extensible |
| Ganado | Multiespecie configurable |
| Datos históricos | No se migran; seeds de demostración |
| Realtime | Laravel Reverb o SSE (fase posterior) |

---

## Documentación

- [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) — stack, capas, multi-tenant,
  auth, billing, seguridad y fases.
- [`docs/MODELO-GANADERO.md`](docs/MODELO-GANADERO.md) — tablas, reglas de
  negocio, API y pruebas del módulo ganadero.
- [`docs/MODELO-GRANJA.md`](docs/MODELO-GRANJA.md) — inventario, clientes,
  ventas, anulaciones y gating.
- [`legacy/README.md`](legacy/README.md) — documentación del sistema anterior.

---

## Fases

1. Fundaciones: repositorio, documentación y frontend base. ✅
2. Laravel + Sanctum + organizaciones + usuarios + roles + multi-tenant. ✅
3. Módulo ganadero completo. ✅
4. Núcleo de granja: productos, inventario, clientes, ventas y reportes. ✅
5. Suscripciones, planes, pagos y gating. ✅ (pago manual)
6. Portal público, formularios y archivos. ⏳
7. Tiempo real, analítica avanzada y reportes ganaderos. ⏳
8. Pruebas E2E, seeds, documentación y despliegue. 🟡

---

## Licencia

Uso institucional — Universidad Nacional de Colombia, Sede Orinoquía.

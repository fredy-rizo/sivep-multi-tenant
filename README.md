# SIVEP 2.0

Sistema de inventario, ventas, producción y gestión ganadera multi-organización
para la Granja El Cairo (Universidad Nacional de Colombia — Sede Orinoquía).

Esta es una **reescritura completa** del sistema anterior:

- **Backend:** PHP + Laravel (API REST).
- **Frontend:** Vue 3 + Vite.
- **Multi-tenant:** aislamiento por organización.
- **Suscripciones:** planes, features, pagos manuales y gating.
- **Módulo nuevo:** información ganadera multiespecie con ID único por animal,
  pesos, vacunas, reproducción y resúmenes mensuales.

El código anterior (Node/Fastify + React) se conserva en `legacy/` únicamente
como referencia funcional.

---

## Estado actual

| Componente | Estado |
| --- | --- |
| Repositorio y control de versiones | ✅ Inicializado |
| Preservación del legado | ✅ En `legacy/` |
| Documentación de arquitectura | ✅ `docs/ARQUITECTURA.md` |
| Modelo ganadero | ✅ `docs/MODELO-GANADERO.md` |
| Frontend Vue 3 | 🟡 Estructura, auth, layout y vistas base |
| Backend Laravel | ⏳ Pendiente de PHP/Composer |
| Base de datos | ⏳ Pendiente |
| Tests | ⏳ Pendiente |

---

## Estructura

```
sivep-multi-tenant/
├── api/                  # Laravel (se crea cuando estén PHP y Composer)
├── web/                  # Vue 3 + Vite (inicializado)
├── legacy/
│   ├── backend/          # Backend Node/Fastify anterior
│   └── frontend/         # Frontend React anterior
├── docs/
│   ├── ARQUITECTURA.md
│   └── MODELO-GANADERO.md
├── .editorconfig
├── .gitignore
└── README.md
```

---

## Requisitos

- PHP 8.3+ con `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`.
- Composer 2.
- MySQL 8 (o SQLite para desarrollo).
- Node 20+ y pnpm.
- Redis (recomendado para colas, caché y Reverb).

---

## Puesta en marcha

### 1. Backend Laravel

Cuando PHP y Composer estén instalados:

```bash
composer create-project laravel/laravel api
cd api
composer require laravel/sanctum spatie/laravel-permission
composer require --dev pestphp/pest laravel/pint
php artisan install:api
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `api/.env` y ejecuta:

```bash
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

> El detalle de la arquitectura, modelos, middleware y fases está en
> [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md).

### 2. Frontend Vue

```bash
cd web
cp .env.example .env
pnpm install
pnpm dev   # http://localhost:5173
```

Vite hace proxy de `/api`, `/sanctum` y `/storage` hacia `http://localhost:8000`.

Si el backend aún no está levantado, la SPA muestra las vistas base y los datos
de ejemplo del módulo ganadero.

---

## Módulos previstos

- **Ganado:** animales, ID único, arete/RFID, genealogía, lotes, pesos, GMD,
  vacunas, tratamientos, reproducción, movimientos y resúmenes mensuales.
- **Inventario:** productos, stock, movimientos y alertas.
- **Ventas:** ítems, totales, pagos, entregas y venta de animales.
- **Clientes:** documentos, comprobantes e historial.
- **Reportes:** operativos, exportaciones y analítica avanzada.
- **Suscripciones:** planes, features, pagos manuales, cupones, licencias y
  servicios de valor agregado.
- **Monetización:** back office para `super_admin`.
- **Usuarios y roles:** `super_admin`, `admin_granja`, `veterinario`, `operario`.

---

## Decisiones de arquitectura

| Tema | Decisión |
| --- | --- |
| Multi-tenant | Una BD con `organization_id` + global scope |
| Autenticación | Sanctum SPA (cookies HttpOnly + CSRF) |
| Autorización | Roles/permisos + policies + gating por plan |
| Facturación | Pago manual con interfaz extensible de pasarela |
| Ganado | Multiespecie configurable |
| Datos históricos | No se migran; seeds de demostración |
| Realtime | Laravel Reverb o SSE |

---

## Documentación

- [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) — stack, capas, multi-tenant,
  auth, billing, seguridad y fases.
- [`docs/MODELO-GANADERO.md`](docs/MODELO-GANADERO.md) — tablas, reglas de
  negocio, API y pruebas del módulo ganadero.
- [`legacy/README.md`](legacy/README.md) — documentación del sistema anterior.

---

## Fases

1. Fundaciones: repositorio, documentación y frontend base. ✅
2. Laravel + Sanctum + organizaciones + usuarios + roles + multi-tenant.
3. Módulo ganadero completo.
4. Núcleo de granja: productos, inventario, clientes, ventas y reportes.
5. Suscripciones, planes, pagos y gating.
6. Portal público, formularios y archivos.
7. Tiempo real, analítica avanzada y reportes ganaderos.
8. Pruebas E2E, seeds, documentación y despliegue.

---

## Licencia

Uso institucional — Universidad Nacional de Colombia, Sede Orinoquía.

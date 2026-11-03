# SIVEP — Web (Vue 3)

SPA de la nueva plataforma SIVEP. Consume la API Laravel (`api/`) mediante
Sanctum SPA: cookies HttpOnly + CSRF, sin tokens en `localStorage`.

## Stack

- Vue 3 + Vite
- Vue Router 4
- Pinia
- Axios
- Tailwind CSS 4
- `@lucide/vue` (iconos)

## Desarrollo

```bash
pnpm install
cp .env.example .env
pnpm dev     # http://localhost:5173
```

Vite hace proxy de `/api`, `/sanctum` y `/storage` hacia
`http://localhost:8000` (Laravel). Si el backend corre en otro puerto u origen,
ajusta `vite.config.js` y `VITE_API_URL`.

## Estructura

```
src/
├── api/http.js          # Axios + cookie CSRF de Sanctum
├── components/          # Layout, placeholders, UI compartida
├── data/                # Datos de ejemplo (se eliminarán al conectar la API)
├── layouts/AppLayout.vue
├── pages/               # Vistas por módulo (ganado, ventas, etc.)
├── router/index.js      # Rutas + guards de sesión y rol
├── stores/              # Pinia: auth y plan
└── style.css            # Tailwind + paleta UNAL
```

## Módulos

- `Ganado`: inventario animal, ficha individual, pesos, vacunas y eventos.
- `Inventario`, `Ventas`, `Clientes`, `Reportes`: placeholders listos para la
  fase de núcleo de granja.
- `Mi plan` y `Monetización`: suscripciones y gating por features.
- `Usuarios`: administración de cuentas (solo `super_admin`).

## Variables

Ver `.env.example`.

# Gestión de usuarios y organizaciones

Administración de cuentas dentro de una organización, con roles, límites por
plan y un selector de organización para el super administrador.

## Roles

| Rol | Alcance |
| --- | --- |
| `super_admin` | Soporte global. No pertenece a una organización; elige una desde la cabecera. |
| `admin_granja` | Administra ganado, inventario, ventas, clientes, usuarios y facturación de su organización. |
| `veterinario` | Consulta ganado, registra eventos y consulta inventario, clientes y ventas. |
| `operario` | Registra animales, eventos, movimientos y ventas; no anula ni administra usuarios. |

Los permisos efectivos viven en `RoleSeeder`. El backend siempre revalida el
permiso y la feature del plan; el frontend solo oculta acciones.

## Reglas de negocio

1. Un usuario pertenece a **una** organización (`organization_id`).
2. El correo es único a nivel global porque es el identificador de login.
3. No se puede desactivar la propia cuenta ni cambiarse el propio rol.
4. La baja de un usuario es lógica: `is_active = false`; conserva su historial.
5. Un administrador de granja solo puede asignar roles
   `admin_granja`, `veterinario` u `operario`.
6. La gestión de usuarios requiere la feature `users.multi` (planes Intermedio y
   Avanzado). En el plan Básico la API responde **402**.

## Aislamiento multi-tenant

`UserController` filtra siempre por la organización resuelta por
`ResolveOrganization` y verifica que el usuario del parámetro pertenezca a ella.
Un usuario de otra organización responde **404** aunque el `id` exista.

## Selector de organización (super_admin)

1. Al iniciar sesión, la cabecera consulta `GET /api/v1/organizations`.
2. Si no hay una organización guardada en `localStorage`
   (`sivep.organization`), selecciona la primera y recarga.
3. Axios agrega `X-Organization-Id` a cada petición.
4. Al cambiar de organización se recarga la SPA para que todas las vistas
   consulten el nuevo tenant y su plan.
5. Para los usuarios normales el backend ignora ese encabezado y usa siempre su
   propia organización.

## Endpoints

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/api/v1/users` | `users.manage` |
| GET | `/api/v1/users/summary` | `users.manage` |
| POST | `/api/v1/users` | `users.manage` |
| GET | `/api/v1/users/{id}` | `users.manage` |
| PUT/PATCH | `/api/v1/users/{id}` | `users.manage` |
| DELETE | `/api/v1/users/{id}` | `users.manage` |

## Pruebas

`api/tests/Feature/UserApiTest.php` cubre:

- listado y resumen de usuarios de la organización;
- creación con rol y organización correctos;
- bloqueo de auto-desactivación y auto-cambio de rol;
- aislamiento frente a usuarios de otra organización;
- correo duplicado;
- gating del plan Básico;
- operario sin permiso de administración;
- super administrador operando sobre la organización seleccionada.

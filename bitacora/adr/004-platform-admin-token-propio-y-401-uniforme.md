# ADR 004: Token propio para el SuperAdmin y 401 uniforme

Estado: aceptado (2026-10-07)

## Decisión
1. Los `platform_admins` tienen su propia tabla de tokens (`platform_admin_tokens`) y su propio filtro `platformAdminOnly`, que **reemplaza** a `auth` en `/platform/*` (no se encadena).
2. `/platform/*` queda fuera del filtro global `tenant`: la excepción vive en `app/Config/Filters.php`, junto a los demás filtros.
3. Códigos: `401` para sin token, token inválido, vencido o de un usuario de Tenant (mismo texto); `403` solo para un `platform_admin` desactivado.
4. El primer `platform_admin` se crea únicamente con `php spark platform:create-admin`. No hay endpoint de alta.
5. Perfil CORS propio (`Cors::$platform`, `cors:platform`): permite `PUT` y `Authorization` y no `X-Tenant`; mismos orígenes que `/public/*`.

## Por qué
- `Auth` resuelve el token contra `users` filtrando por Tenant y `/platform/*` no tiene Tenant: encadenarlos haría imposible loguearse.
- Un token de usuario no debe existir para el panel (ni al revés): con tablas separadas no hay forma de que uno resuelva como el otro. Responder `403` a un token de Tenant revelaría que el token es válido en otra parte.
- Sin la excepción de `tenant`, `/platform/*` respondería 400 en producción y cargaría `demo` en desarrollo.
- Un endpoint de alta de platform_admins no tendría con qué autenticarse para usarse; el comando evita además dejar la contraseña en el historial del shell (se pide por prompt o se genera).

## Consecuencias
- Toda ruta nueva de Tenant debe llevar `auth`; `PlatformRoutesTest` revisa los filtros efectivos de todas las rutas y falla si una queda sin `auth` o sin `tenant`.
- Desactivar a un `platform_admin` corta su acceso aunque tenga un token vigente (el filtro revisa `is_active` en cada request).
- El alta de un Tenant crea su primer ADMIN con contraseña temporal y sin email hasta el Paso 4.

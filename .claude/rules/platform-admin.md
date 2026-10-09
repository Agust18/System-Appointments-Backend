# Panel de plataforma (SuperAdmin)

Un `platform_admin` administra los Tenants: listar, crear y suspender/reactivar. **No pertenece a ningún Tenant** (ADR 003): vive en `platform_admins`, con sus propios tokens en `platform_admin_tokens`. Las dos tablas son globales (sin `tenant_id`) y sus Models no usan `TenantContext`.

Código: `app/Controllers/Platform/`, `app/Services/Platform/` (+ `Tenant/`), `app/Models/Platform/PlatformAdminModel`, `app/Entity/Platform/`, `app/Filters/PlatformAdminOnly`, `app/Commands/CreatePlatformAdminCommand`.

## Endpoints (`app/Config/Routes.php`, grupo `platform`)

| Método y ruta | Filtros | Hace |
|---|---|---|
| `POST /platform/login` | `cors:platform`, `throttle:10,60` | `200` con `{token, admin}` |
| `GET /platform/tenants` | `cors:platform`, `platformAdminOnly` | `{data, total}`; filtros `search` (nombre o slug), `status`, `page`, `size` |
| `POST /platform/tenants` | idem | `201`. Crea el Tenant **y su primer ADMIN** en una transacción |
| `GET /platform/tenants/{id}` | idem | Detalle: `{id, slug, name, status}`. `404` si no existe |
| `PUT /platform/tenants/{id}` | idem | Cambia `name` y/o `status` (`active`/`suspended`). Suspender y reactivar son el mismo PUT |
| `GET /platform/tenants/{id}/settings` | idem | Configuración completa del Tenant (`tenant_settings`): `name` visible, `replyToEmail`, `frontendUrl`, `receptionEmail`, `receptionEmailEnabled`, `primaryColor`, `secondaryColor` (branding). `404` si el Tenant no existe |
| `PUT /platform/tenants/{id}/settings` | idem | Edita esa configuración. Campo ausente = sin cambio; `null`/`""` limpia `replyToEmail`, `frontendUrl` y `receptionEmail` (`name` y `receptionEmailEnabled` no se limpian). Body sin campos = `422`. Funciona con Tenants suspendidos |
| `OPTIONS /platform/*` | `cors:platform` | Preflight |

**El cliente no edita su configuración**: no existe `PUT /tenant/config`; solo `GET /tenant/config` (público, `{slug, name, primaryColor, secondaryColor}`). Los colores son `#RRGGBB` (se guardan en mayúsculas; `null`/`""` los limpia; nulos = el frontend usa su tema por defecto). Logo por Tenant: pendiente, requiere subida de archivos. `frontendUrl` se valida con `App\Tenancy\FrontendUrl`: solo origen `https://host[:puerto]` (`http` fuera de producción), sin userinfo, path, query ni fragment; se guarda con el host en minúsculas y sin `/` final. `TenantSettingsModel::findByTenantId()/update()` reciben el id explícito (el panel no tiene `TenantContext`) y filtran por `tenant_id`. Pendiente: restringir el host al dominio de la plataforma si el cliente llegara a editar su config.

No hay `DELETE` ni `PATCH`: los datos de un Tenant se conservan. El `slug` **no se modifica** (es el subdominio y el valor de `X-Tenant`).

## Autenticación: `platformAdminOnly` REEMPLAZA a `auth`

`Auth` resuelve el token contra `users` filtrando por Tenant, y `/platform/*` no tiene Tenant. Por eso `PlatformAdminOnly` valida el token contra `platform_admin_tokens` (`PlatformTokenValidatorService`) y **solo** guarda `platformAdminId` en sesión (nunca `userId` ni `roleId`).

| Situación | Respuesta |
|---|---|
| Sin header, token inválido, vencido o **de un usuario de Tenant** | `401` (mismo texto: no filtra información) |
| `platform_admin` desactivado | `403` |
| Login con email inexistente o contraseña incorrecta | `401` neutral (se verifica un hash falso para igualar el tiempo) |
| Login de un admin desactivado con la contraseña correcta | `403` (después de verificar la contraseña) |

`PlatformAdminModel::findByToken` no filtra por `is_active` a propósito: así el filtro distingue 403 de 401. Desactivar un admin corta su acceso aunque tenga un token vigente. El token es `bin2hex(random_bytes(32))` con vigencia de 24 h; el login borra los vencidos de ese admin.

## `/platform/*` no tiene Tenant

El filtro global `tenant` se exceptúa en `app/Config/Filters.php` (`'tenant' => ['except' => ['platform', 'platform/*']]`; `platform/*` no cubre `platform` a secas). Sin esa excepción `/platform/*` respondería 400 en producción y cargaría `demo` en desarrollo. `TenantContext` queda vacío: si un Model de Tenant se llama por error ahí, lanza `TenantNotResolvedException`.

`PlatformRoutesTest` verifica los filtros efectivos de cada ruta y que **toda ruta de Tenant siga teniendo `tenant` + `auth`** (salvo `/public/*` y los flujos de acceso: login, registro, verify-email, resend-verification, forgot-password, reset-password).

## CORS

Perfil propio `Config\Cors::$platform` (se usa con `cors:platform`): permite `PUT` y `Authorization`, **no** `X-Tenant`, y usa los mismos `cors.allowedOrigins` / `cors.allowedOriginsPatterns` que `/public/*`. `$default` no cambia. Si el panel va a otro dominio, hace falta una lista de orígenes aparte. Con un único origen configurado, CI4 siempre devuelve ese origen en `Access-Control-Allow-Origin` (el navegador bloquea al resto). CORS en las rutas de Tenant sigue pendiente (`bitacora/pendiente/cors-en-rutas-privadas.md`).

## Alta de un Tenant (`TenantCreatorService`)

Body: `slug`, `name`, `adminFirstName`, `adminLastName`, `adminEmail`, `adminDni`, `frontendUrl` (**obligatorio**) y `adminPassword` (**opcional**, mín. 8; vacío = ausente: el ADMIN nace sin contraseña y la elige con el link del email de bienvenida).

1. Pre-valida fuera de la transacción: slug con formato de subdominio (`^[a-z0-9]([a-z0-9-]*[a-z0-9])?$`, máx. 63), no reservado (`TenantCreateValidatorService::RESERVED_SLUGS`: `422`) y no repetido (`409`); `frontendUrl` válido según `App\Tenancy\FrontendUrl` (`422`, clave `frontendUrl`; `http` solo fuera de producción).
2. Una sola transacción (`UserCreatorService` no abre otra: no se anidan): inserta el Tenant, verifica el resultado (no null y `transStatus()`), `TenantContext::set($nuevo)` y crea el ADMIN con `isVerified = true`.
   Entre el Tenant y el ADMIN se inserta su fila de `tenant_settings` (`TenantSettings::defaultsFor`: `name` = nombre del Tenant, `frontend_url` = el del body normalizado, el resto NULL).
3. Ante cualquier error: `transRollback()`. En el `finally` restaura el `TenantContext` previo (`clear()` si estaba vacío).
4. **Email de bienvenida** (`SendTenantAdminWelcomeEmailService`): después del commit, con el Tenant nuevo en el `TenantContext` (se restaura después), sale **siempre** (con o sin `adminPassword`) al email del ADMIN, con un link `{frontend_url}/reset-password?token=...` y **sin contraseña**. El token es del tipo `password_reset` (lo acepta `/reset-password`) pero dura **72 h** (`TenantAdminWelcomeTokenService`; el reset normal dura 1 h). El Service nunca lanza: si el SMTP falla, el alta **no se deshace** y la respuesta trae `welcomeEmailSent: false` (`TenantCreatedResponse`); el ADMIN puede pedir otro link con `forgot-password` (un usuario sin contraseña recibe "Activá tu cuenta"). Requiere que exista el rol `admin` en `roles`: en el deploy, después de `php spark migrate`, correr `php spark db:seed RolesSeeder` (idempotente; ver `bitacora/pendiente/roles-seeder.md`).

Un Tenant suspendido responde `403` en todas sus rutas (filtro `tenant`), conserva sus datos y se puede seguir gestionando desde el panel.

## Crear el primer `platform_admin`

Solo por comando (no hay endpoint: nadie tendría con qué autenticarse para usarlo):

```bash
docker exec -it turnero_app sh -c "cd /var/www/html && php spark platform:create-admin --email root@dominio.com --first-name Root --last-name Admin"
```

La contraseña no se pasa por argumento (quedaría en el historial del shell): se pide por prompt (mínimo 12 caracteres) o se genera con `--generate-password` y se muestra una sola vez. En producción lo ejecuta una persona en el deploy.

## Tests

`tests/database/Platform/` (base común `PlatformEndpointTestCase`), `tests/unit/Platform/`, `tests/unit/Config/Platform*Test.php`. Los tests que crean Tenants con usuarios deben limpiar en `tearDown`: el mismo email/DNI en dos Tenants impide revertir la migración `AddTenantIdToUsersAndPatients` en la clase siguiente (limitación del ADR 003).

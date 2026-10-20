# Auth y RBAC

## Roles (`app/Constants/Role.php`)
ADMIN=1, MEDICAL=2, PATIENT=3, RECEPTION=4.

## Los roles no tienen CRUD
No existe `/roles`: la tabla `roles` es global (sin `tenant_id`) y un rol sin
código que lo respalde no tiene funciones. Los roles los agrega el
desarrollador, en un mismo cambio:
1. Constante en `App\Constants\Role` **y** su entrada en `Role::NAMES` (id => nombre).
   `RolesSeeder` y `Role::isValid()` leen de `NAMES`: es la única lista.
2. Sembrarlo: `php spark db:seed RolesSeeder` (o una migración). La FK
   `users.role_id` apunta a `roles.id`.
3. Constante y etiqueta en `constants/role.js` del frontend (`ROLES` + `ROLE_OPTIONS`).
4. Permisos: Filters y `PermissionsService`.

El `roleId` al crear/editar usuarios se valida en memoria con
`Role::isValid()` (`RoleNotFoundException`, 404); no consulta la tabla. Por eso
**el seeder tiene que correr en el deploy**: sin la fila, el `INSERT` falla por
la FK y responde `UserCannotBeCreatedException` (500).

## Cómo funciona el token
El filter `Auth` (`app/Filters/Auth.php`) lee el header `HTTP_AUTHORIZATION`,
lo valida con `AuthTokenValidatorService` y guarda en sesión:
- `session()->get('userId')`
- `session()->get('roleId')` (usar constantes de `App\Constants\Role`)

Si falta el header, el token no existe o está expirado, el Filter devuelve
un `Response` 401 directamente — nunca lanza excepción (respeta la regla de
que los Filters cortan la cadena con Response, no con excepciones).

## Filters RBAC (`app/Filters/`)
| Filter | Acceso |
|---|---|
| `auth` | Cualquier usuario autenticado |
| `adminOnly` | Solo ADMIN |
| `adminOrReception` | ADMIN o RECEPTION. Antes existía `receptionUserCreate` como filtro separado para `POST /patients`, pero se unificó acá al comprobarse que eran funcionalmente idénticos (mismo chequeo de rol, mismo 403); no verifica coherencia entre el recurso y el `roleId` del body (eso se valida fail-fast en `PatientQuickCreatorService`/`MedicalQuickCreatorService`, ver abajo) |
| `appointmentPermission` | Admin/recepción sin restricción; paciente solo cancela su turno `confirmed` (vía `PermissionsService::canCancelAppointment()`; el validator exige que mande solo `status: cancelled` y que el turno no haya pasado); médico solo modifica sus propios turnos (vía `PermissionsService::canEditAppointment()`), nunca elimina |
| `appointmentCreatePermission` | ADMIN, RECEPTION y PATIENT pueden crear turnos; MEDICAL no |
| `appointmentViewPermission` | Admin/recepción sin restricción; paciente/médico solo ven turnos propios, vía `PermissionsService::canViewAppointment()` |
| `medicalViewPermission` | Admin/recepción sin restricción; paciente puede ver cualquier médico por `/medicals/{id}` pero **no** por `/medicals/user/{userId}` (esa ruta es solo para que el médico consulte su propio perfil — asimetría intencional); médico solo ve su propio perfil por cualquiera de las dos rutas |
| `patientViewPermission` | Admin/recepción sin restricción; el paciente ya no es usuario (no hay `/patients/user/{id}`); médico ve pacientes con los que tuvo al menos un turno, vía `PermissionsService::canViewPatient()` → `AppointmentModel::hasRelationWithPatient()` (query `EXISTS`, no trae todos los turnos) |

| `platformAdminOnly` | **Solo `/platform/*`; reemplaza a `auth`** (no se encadena con él). Valida el token contra `platform_admin_tokens`: `401` si falta, es inválido, venció o es de un usuario de Tenant; `403` si el `platform_admin` está desactivado. Guarda solo `platformAdminId` en sesión. Detalle en `platform-admin.md` |

Los Filters que verifican ownership instancian Models directamente
(permitido y documentado en la arquitectura).

## Rutas sin auth: `throttle` y `cors`
Las rutas `/public/*` (reserva pública) no llevan
`auth`. Las de `/public/*` se protegen con dos Filters que **no son de RBAC**
(no miran roles ni sesión):

| Filter | Qué hace |
|---|---|
| `cors` | `CodeIgniter\Filters\Cors`. Solo en `/public/*`, para los orígenes de `cors.allowedOrigins` / `cors.allowedOriginsPatterns` del `.env`. Sin orígenes no agrega headers. |
| `throttle:{capacidad},{segundos}` | `app/Filters/Throttle.php`. Límite por IP (token bucket sobre el cache). Al excederse devuelve un `Response` 429 con `Retry-After` (no lanza excepción, como los demás Filters). Lecturas `throttle:60,60`, reserva `throttle:5,60`. |

`cors` tiene que ir **antes** que `throttle` en la lista de la ruta: el 429 se
arma sobre la respuesta a la que `cors` ya agregó sus headers.

## Bloqueo por DNI
`PublicIdentityAttemptLimiter` ya no lo usa nadie: la reserva pública dejó de comparar
teléfonos (el paciente es una ficha identificada solo por DNI). Se reutilizará en `/consulta`
(fase 3 de `bitacora/pendiente/paciente-sin-usuario.md`).


## `PermissionsService` (`app/Services/Permissions/PermissionsService.php`)
Centraliza las reglas de rol + ownership que antes estaban duplicadas entre
Filters. Expone `canViewPatient()`, `canViewAppointment()`, `canEditAppointment()`,
`canCancelAppointment()`, `canDeleteAppointment()`. Todas reciben `$roleId` y
`$userId` de la sesión explícitos (no los lee de `session()` internamente),
para mantenerse testeable sin mockear sesión.

No valida nada relacionado al body de la request (eso es responsabilidad de
los Validator Services en la capa de Service, no del Filter ni de
`PermissionsService`).

## `ResolvesEntityByRouteSegment` (`app/Filters/Support/ResolvesEntityByRouteSegment.php`)
Trait usado por `MedicalViewPermission` para resolver
si la ruta es `/recurso/user/{userId}` o `/recurso/{id}`, evitando duplicar el
parseo de segmentos. Devuelve `[$entity, $byUserId]`; el flag `$byUserId` existe
porque `MedicalViewPermission` tiene una regla de autorización distinta según
la forma de la ruta (ver arriba). Depende de que el recurso sea el primer
segmento de la URL (`segments[1]`/`segments[2]`) — si cambia el prefijo de
rutas en el futuro, hay que ajustar el trait en un solo lugar.

## Namespaces
- `app/Controllers/Auth/` — login, verify-email, forgot-password, reset-password. No siguen CRUD estándar.
- `app/Controllers/User/` — CRUD de usuarios (adminOnly).
- `app/Services/Auth/` — Services de auth (login, token validation, verificación, reset).

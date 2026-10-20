# Auth y RBAC

## Roles (`app/Constants/Role.php`)
ADMIN=1, MEDICAL=2, RECEPTION=4. El id 3 fue del rol "paciente" y **no se reutiliza**: el paciente no es un usuario, es una ficha en `patients` sin cuenta ni login (ver `public-booking.md`).

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
| `role:{roles}` | Solo los roles listados en la ruta, por nombre de `Role::NAMES`: `role:admin`, `role:admin,reception`, `role:admin,reception,medical`. Reemplaza a los viejos `adminOnly`, `adminOrReception`, `adminReceptionOrMedical` y `appointmentCreatePermission`. Un nombre desconocido o una ruta sin argumentos no deja pasar a nadie (falla cerrado); 403 como `Response`. No verifica coherencia entre el recurso y el `roleId` del body |
| `appointmentPermission` | Admin/recepción sin restricción; médico solo modifica sus propios turnos (vía `PermissionsService::canEditAppointment()`), nunca elimina. Cualquier otro rol: 403 |
| `appointmentViewPermission` | Admin/recepción sin restricción; el médico solo ve sus turnos, vía `PermissionsService::canViewAppointment()` |
| `medicalViewPermission` | Admin/recepción sin restricción; el médico solo ve su propio perfil, por `/medicals/{id}` o por `/medicals/user/{userId}` |
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
`PublicIdentityAttemptLimiter` lo usa `/consulta` (`PublicAppointmentAccessResolverService`): 5 intentos
fallidos con DNI + código bloquean ese DNI una hora, con el mismo error neutral. Ver `public-booking.md`.

## `PermissionsService` (`app/Services/Permissions/PermissionsService.php`)
Única fuente de las reglas de propiedad (qué puede ver o editar cada rol). Los
filtros `appointmentPermission`, `appointmentViewPermission`, `medicalViewPermission`
y `patientViewPermission` solo resuelven la entidad y le preguntan a este Service.
Expone `hasFullAccess()` (ADMIN y RECEPTION), `canViewPatient()`, `canViewMedical()`,
`canViewAppointment()`, `canEditAppointment()` y `canDeleteAppointment()`. Qué roles
entran a cada endpoint se declara en `Routes.php` con `role:...`; acá solo vive la
propiedad de los datos. Los builders de filtros de listados
(`AppointmentFilterBuilderService`, `PatientFilterBuilderService`, `AppointmentStatsService`)
restringen la lista al médico y no preguntan "¿puede ver este?", así que siguen aparte. Todas reciben `$roleId` y
`$userId` de la sesión explícitos (no los lee de `session()` internamente),
para mantenerse testeable sin mockear sesión.

No valida nada relacionado al body de la request (eso es responsabilidad de
los Validator Services en la capa de Service, no del Filter ni de
`PermissionsService`).

## `ResolvesEntityByRouteSegment` (`app/Filters/Support/ResolvesEntityByRouteSegment.php`)
Trait usado por `MedicalViewPermission` para resolver si la ruta es `/recurso/user/{userId}`
o `/recurso/{id}`, evitando duplicar el parseo de segmentos. Devuelve `[$entity, $byUserId]`
(el flag solo cambia el texto del 403). Depende de que el recurso sea el primer segmento de la
URL (`segments[1]`/`segments[2]`): si cambia el prefijo de rutas hay que ajustar el trait en un solo lugar.

## Namespaces
- `app/Controllers/Auth/` — login, forgot-password, reset-password. No siguen CRUD estándar.
- `app/Controllers/User/` — CRUD de usuarios (adminOnly).
- `app/Services/Auth/` — Services de auth (login, token validation, reset).

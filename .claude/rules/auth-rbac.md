# Auth y RBAC

## Roles (`app/Constants/Role.php`)
ADMIN=1, MEDICAL=2, PATIENT=3, RECEPTION=4.

## Cómo funciona el token
El filter `Auth` (`app/Filters/Auth.php`) lee el header `HTTP_AUTHORIZATION`,
lo valida con `TokenValidatorService` y guarda en sesión:
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
| `appointmentPermission` | Admin/recepción sin restricción; paciente solo cancela su turno pendiente (vía `PermissionsService::canCancelAppointment()`); médico solo modifica sus propios turnos (vía `PermissionsService::canEditAppointment()`), nunca elimina |
| `appointmentCreatePermission` | ADMIN, RECEPTION y PATIENT pueden crear turnos; MEDICAL no |
| `appointmentViewPermission` | Admin/recepción sin restricción; paciente/médico solo ven turnos propios, vía `PermissionsService::canViewAppointment()` |
| `medicalViewPermission` | Admin/recepción sin restricción; paciente puede ver cualquier médico por `/medicals/{id}` pero **no** por `/medicals/user/{userId}` (esa ruta es solo para que el médico consulte su propio perfil — asimetría intencional); médico solo ve su propio perfil por cualquiera de las dos rutas |
| `patientViewPermission` | Admin/recepción sin restricción; paciente solo ve su propio perfil; médico ve pacientes con los que tuvo al menos un turno, vía `PermissionsService::canViewPatient()` → `AppointmentModel::hasRelationWithPatient()` (query `EXISTS`, no trae todos los turnos) |

Los Filters que verifican ownership instancian Models directamente
(permitido y documentado en la arquitectura).

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
Trait usado por `MedicalViewPermission` y `PatientViewPermission` para resolver
si la ruta es `/recurso/user/{userId}` o `/recurso/{id}`, evitando duplicar el
parseo de segmentos. Devuelve `[$entity, $byUserId]`; el flag `$byUserId` existe
porque `MedicalViewPermission` tiene una regla de autorización distinta según
la forma de la ruta (ver arriba). Depende de que el recurso sea el primer
segmento de la URL (`segments[1]`/`segments[2]`) — si cambia el prefijo de
rutas en el futuro, hay que ajustar el trait en un solo lugar.

## Namespaces
- `app/Controllers/Auth/` — login, verify-email, forgot-password, reset-password. No siguen CRUD estándar.
- `app/Controllers/User/` — CRUD de usuarios (adminOnly).
- `app/Services/auth/` (minúscula) — Services de auth (login, token validation, verificación, reset).

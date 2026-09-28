# Auth y RBAC

## Roles (`app/Constants/Role.php`)
ADMIN=1, MEDICAL=2, PATIENT=3, RECEPTION=4.

## Cómo funciona el token
El filter `Auth` (`app/Filters/Auth.php`) lee el header `HTTP_AUTHORIZATION`,
lo valida con `TokenValidatorService` y guarda en sesión:
- `session()->get('userId')`
- `session()->get('roleId')` (usar constantes de `App\Constants\Role`)

## Filters RBAC (`app/Filters/`)
| Filter | Acceso |
|---|---|
| `auth` | Cualquier usuario autenticado |
| `adminOnly` | Solo ADMIN |
| `adminOrReception` | ADMIN o RECEPTION |
| `receptionUserCreate` | ADMIN o RECEPTION; acepta argumento de tipo (`patient`, `medical`) |
| `appointmentPermission` | Admin/recepción sin restricción; paciente solo cancela su turno pendiente; médico solo modifica sus propios turnos |
| `appointmentCreatePermission`, `appointmentViewPermission` | Variantes para crear/ver |
| `medicalViewPermission`, `patientViewPermission` | Ownership checks de perfiles |

Los Filters que verifican ownership instancian Models directamente
(permitido y documentado en la arquitectura).

## Namespaces
- `app/Controllers/Auth/` — login, verify-email, forgot-password, reset-password. No siguen CRUD estándar.
- `app/Controllers/User/` — CRUD de usuarios (adminOnly).
- `app/Services/auth/` (minúscula) — Services de auth (login, token validation, verificación, reset).
# Reserva pública de turnos

Un paciente reserva sin cuenta ni login: el paciente **no es un usuario**, es una ficha en `patients` (nombre, apellido, email, DNI, teléfono) identificada por DNI dentro del Tenant (`UNIQUE(tenant_id, dni)`). Si el DNI ya existe se reutiliza la ficha **sin tocar sus datos**; el teléfono ya no es un segundo factor.
Todo vive bajo `/public/*`.

Código principal: `PublicAppointmentCreatorService`, `PatientCreatorService::findOrCreateByDni()`, filtro `Throttle`. `PublicIdentityAttemptLimiter` queda sin uso hasta la fase 3 (`/consulta`).
Contrato para el frontend: `bitacora/hecho/contrato-*.md`.

## Endpoints (`app/Config/Routes.php`)

Sin header `Authorization`. Todos pasan por `cors` y después por `throttle` (el orden importa, ver Seguridad).

| Método y ruta | Throttle por IP | Devuelve |
|---|---|---|
| `GET /public/specialties` | 60/min | Especialidades |
| `GET /public/services` | 60/min | Servicios activos |
| `GET /public/medicals/available?specialtyId=` | 60/min | Médicos con `users.is_active = 1` (`medicals` no tiene `is_active`) |
| `GET /public/medicals/{id}/services` | 60/min | Servicios del médico (duración y precio efectivos) |
| `GET /public/appointments/availability?medicalId=&serviceId=&fecha=` (o `from`/`to`) | 60/min | Slots. Controller propio, `PublicAppointmentAvailabilityGetController` (no acepta `excludeAppointmentId`, que es solo de la ruta autenticada) |
| `POST /public/appointments` | 5/min | `201` con `PublicAppointmentResponse` (`id, fecha, hora, medicoNombre, servicioNombre, status`) |
| `OPTIONS /public/*` | no gasta cupo | Preflight CORS |

Los listados ignoran `page`, `size`, `search` e `isActive`: tope fijo de 100 ítems.

## Flujo de `POST /public/appointments`

`PublicAppointmentPostController` normaliza el input (`IdentityNormalizer`), valida `PublicAppointmentRequest` y llama a `PublicAppointmentCreatorService::create()`. Cualquier `status` o `patientId` del body se ignora.

1. **Validar el turno** (`PublicAppointmentCreateValidatorService`): médico existente, usuario del médico activo (único `is_active`; `medicals.is_active` se eliminó), fecha no pasada, servicio activo y ofrecido por el médico, y que el bloque completo quepa en un rango horario del día. Corre antes de tocar `patients`: si falla no se escribe nada.
2. **Resolver la ficha** (`PatientCreatorService::findOrCreateByDni()`): busca por DNI en el Tenant; si existe la devuelve sin modificarla, si no la crea. Sin lock ni transacción: dos solicitudes simultáneas del mismo DNI las resuelve el `UNIQUE(tenant_id, dni)` (la perdedora recupera la ficha ganadora).
3. **Tope de turnos**: máximo 3 turnos futuros `pending`/`confirmed` por paciente (`MAX_ACTIVE_FUTURE_APPOINTMENTS`). Es un límite blando: sin lock, dos solicitudes simultáneas del mismo paciente pueden pasarlo por uno.
4. **Reservar** con `AppointmentBookingService::book()` (ver `booking-atomico.md`). El turno nace `confirmed`.
5. **Avisar** con `PublicAppointmentCreatedNotificationService::notify()`: email al paciente y a recepción (ver `email.md`). Fuera de toda transacción y a prueba de fallos.

**Los pasos no van en una transacción exterior, a propósito.** `AppointmentBookingService` libera su `GET_LOCK` en un `finally`; con una transacción exterior su commit no commitearía y el lock se liberaría antes del commit real, rompiendo la reserva atómica. Si se pierde la carrera por el horario después del paso 2, queda la ficha del paciente sin turno: es inofensiva, el próximo intento con el mismo DNI la reutiliza.

## Identidad: la ficha se identifica solo por DNI

| Situación | Resultado |
|---|---|
| DNI nuevo en el Tenant | Crea la ficha con los datos del formulario |
| DNI existente | Reutiliza la ficha **sin modificar nada** (nombre, email y teléfono tipeados se ignoran) |

- No hay `409` de identidad: ya no existen los casos "teléfono distinto" ni "email de otro usuario". El mismo DNI en dos Tenants son dos fichas distintas.
- **Riesgo asumido**: reservar con un DNI ajeno ya no tiene segundo factor. Lo mitigan el tope de 3 turnos, el throttle por IP, no pisar datos de la ficha y que recepción pueda cancelar. Si el paciente no tenía email guardado, no recibe avisos (recepción lo llama).
- Caso familiar (padre/hijo): un hijo con DNI propio es una ficha propia; un hijo sin DNI no puede reservar (el DNI es obligatorio). Perfiles de familia: `bitacora/features/perfiles-de-familia.md`.

## Seguridad

- **Throttle por IP** (`app/Filters/Throttle.php`, `throttle:{capacidad},{segundos}`): token bucket sobre el cache de CodeIgniter. "60 por minuto" = ráfaga de 60 y después 1 por segundo; "5 por minuto" = ráfaga de 5 y después 1 cada 12 s. Cada combinación IP + método + límite tiene su bucket, así que los GET no gastan el cupo del POST. Al excederse devuelve `429` con `Retry-After` (segundos). El preflight `OPTIONS` no consume cupo.
- **`cors` antes que `throttle`** en la lista de filtros de la ruta: el 429 se arma sobre la respuesta a la que `cors` ya agregó sus headers; sin ellos el navegador no deja leer el 429.
- **IP real**: sin `app.trustedProxies` se usa `REMOTE_ADDR`. Detrás de un proxy es obligatorio configurarlo, y el proxy debe **sobrescribir** el header (no agregarle), porque se toma la primera IP.
- **Bloqueo por DNI** (`PublicIdentityAttemptLimiter`): hoy **sin uso** (la reserva ya no compara teléfonos). Se reutilizará en `/consulta` (fase 3, `bitacora/pendiente/paciente-sin-usuario.md`).
- **Falla abierto**: si el cache activo es `DummyHandler` (p. ej. `writable/cache` sin permisos) el throttle y el bloqueo por DNI no limitan, sin ningún error. `Throttle` deja un `critical` en el log en cada request.
- **CORS** solo en `/public/*` y solo para `cors.allowedOrigins` / `cors.allowedOriginsPatterns`. Sin orígenes configurados no se envía ningún header. Limitación conocida: `exposedHeaders` está vacío, así que con el SPA en otro dominio el navegador no puede leer `Retry-After` (ver `bitacora/pendiente/cors-exponer-retry-after.md`).

## Respuestas de error

| Código | Cuándo |
|---|---|
| `422` | Validación (`errors` por campo) |
| `409` | Horario ocupado; médico no disponible; tope de 3 turnos |
| `423` | El profesional está procesando otra reserva (reintentar en segundos) |
| `429` | Throttle por IP, con `Retry-After` |

## Checklist de deploy

Los endpoints `/public/*` **no deben exponerse a internet** hasta completar el checklist de `bitacora/pendiente/deploy-*.md`. Resumen:
`app.trustedProxies`, `writable/cache` escribible, cache compartido si hay varias instancias, `cors.*`, migraciones probadas en una copia (UNIQUE de `users.dni` y `patients.user_id`), `EMAIL_*` y `reception.email`.

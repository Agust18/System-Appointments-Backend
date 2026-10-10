# Reserva pública de turnos

Un paciente reserva sin cuenta ni login. El teléfono es el segundo factor que acompaña al DNI.
Todo vive bajo `/public/*` (más `POST /patients/register`, que no es parte de la reserva pero comparte usuarios sin cuenta: si el DNI es de uno sin cuenta con el mismo email, usa la contraseña del body, crea el paciente si falta y envía el email de verificación; responde siempre `201`, ver `email.md`).

Código principal: `PublicAppointmentCreatorService`, `PatientGuestResolverService`, `PublicIdentityAttemptLimiter`, filtro `Throttle`.
Contrato para el frontend: `bitacora/hecho/contrato-*.md`.

## Endpoints (`app/Config/Routes.php`)

Sin header `Authorization`. Todos pasan por `cors` y después por `throttle` (el orden importa, ver Seguridad).

| Método y ruta | Throttle por IP | Devuelve |
|---|---|---|
| `GET /public/specialties` | 60/min | Especialidades |
| `GET /public/services` | 60/min | Servicios activos |
| `GET /public/medicals/available?specialtyId=` | 60/min | Médicos con `users.is_active = 1` (`medicals` no tiene `is_active`) |
| `GET /public/medicals/{id}/services` | 60/min | Servicios del médico (duración y precio efectivos) |
| `GET /public/appointments/availability?medicalId=&serviceId=&fecha=` (o `from`/`to`) | 60/min | Slots. Reusa `AppointmentAvailabilityGetController` |
| `POST /public/appointments` | 5/min | `201` con `PublicAppointmentResponse` (`id, fecha, hora, medicoNombre, servicioNombre, status`) |
| `OPTIONS /public/*` | no gasta cupo | Preflight CORS |

Los listados ignoran `page`, `size`, `search` e `isActive`: tope fijo de 100 ítems.

## Flujo de `POST /public/appointments`

`PublicAppointmentPostController` normaliza el input (`IdentityNormalizer`), valida `PublicAppointmentRequest` y llama a `PublicAppointmentCreatorService::create()`. Cualquier `status` o `patientId` del body se ignora.

1. **Validar el turno** (`PublicAppointmentCreateValidatorService`): médico existente, usuario del médico activo (único `is_active`; `medicals.is_active` se eliminó), fecha no pasada, servicio activo y ofrecido por el médico, y que el bloque completo quepa en un rango horario del día. Corre antes de tocar `users`/`patients`: si falla no se escribe nada.
2. **Resolver la identidad** (`PatientGuestResolverService::resolve()`): bloqueo por DNI, `GET_LOCK('public_identity_{tenantId}_{dni}')` y transacción propia. Casos A–E abajo.
3. **Tope de turnos**: máximo 3 turnos futuros `pending`/`confirmed` por paciente (`MAX_ACTIVE_FUTURE_APPOINTMENTS`). Es un límite blando: sin lock, dos solicitudes simultáneas del mismo paciente pueden pasarlo por uno.
4. **Reservar** con `AppointmentBookingService::book()` (ver `booking-atomico.md`). El turno nace `confirmed`.
5. **Avisar** con `PublicAppointmentCreatedNotificationService::notify()`: email al paciente y a recepción (ver `email.md`). Fuera de toda transacción y a prueba de fallos.

**Los pasos no van en una transacción exterior, a propósito.** `AppointmentBookingService` libera su `GET_LOCK` en un `finally`; con una transacción exterior su commit no commitearía y el lock se liberaría antes del commit real, rompiendo la reserva atómica. Cada paso tiene su propio lock + transacción. Si se pierde la carrera por el horario después del paso 2, queda un usuario sin cuenta con su paciente y sin turno: es inofensivo, el próximo intento con el mismo DNI + teléfono lo reutiliza.

**Nunca llamar a `PatientGuestResolverService` desde dentro de otra transacción**, por el mismo motivo.

## Identidad: casos A–E (`PatientGuestResolverService`)

El usuario creado nace sin cuenta (`password` NULL, `hasAccount = false`) y nunca se modifican los datos de un usuario existente.

| Caso | Situación | Resultado |
|---|---|---|
| **A** | DNI nuevo y email libre | Crea usuario sin cuenta + paciente |
| **B / E** | DNI existente, rol paciente, activo y teléfono coincide | Reutiliza su paciente |
| **D** | Igual que B, pero el usuario no tiene fila en `patients` | Crea el paciente |
| **B'** | DNI existente con otro teléfono, **sin** teléfono guardado, de otro rol o inactivo | `409` neutral |
| **C** | DNI nuevo pero el email pertenece a otro usuario | `409` neutral |

- **B' y C devuelven la misma excepción** (`PublicBookingIdentityException`, 409) con el mismo texto, para no revelar cuál ocurrió ni si un DNI existe.
- Un usuario **sin teléfono guardado nunca coincide**: completarlo desde la reserva pública permitiría apropiarse de un DNI ajeno conociendo solo el DNI. Recepción lo carga con `PUT /patients/{id}`.
- El teléfono se compara normalizado y con `hash_equals`.
- Un `INSERT` duplicado por una solicitud concurrente (mismo email, otro DNI) se trata como caso C.
- Cada rechazo se loguea (`notice`) con el DNI hasheado: el log no contiene datos personales.

### Caso familiar (padre/hijo) — limitación conocida
El padre reserva para un hijo:
- Hijo con **DNI propio** y email propio o teléfono distinto del titular: caso A, sin problema.
- Hijo con el **email del padre**: caso C, `409` neutral.
- Hijo **sin DNI**: no puede reservar (el DNI es obligatorio).
- Solución actual: lo resuelve recepción a mano. Feature futura: perfiles de familia (`bitacora/features/perfiles-de-familia.md`).

## Seguridad

- **Throttle por IP** (`app/Filters/Throttle.php`, `throttle:{capacidad},{segundos}`): token bucket sobre el cache de CodeIgniter. "60 por minuto" = ráfaga de 60 y después 1 por segundo; "5 por minuto" = ráfaga de 5 y después 1 cada 12 s. Cada combinación IP + método + límite tiene su bucket, así que los GET no gastan el cupo del POST. Al excederse devuelve `429` con `Retry-After` (segundos). El preflight `OPTIONS` no consume cupo.
- **`cors` antes que `throttle`** en la lista de filtros de la ruta: el 429 se arma sobre la respuesta a la que `cors` ya agregó sus headers; sin ellos el navegador no deja leer el 429.
- **IP real**: sin `app.trustedProxies` se usa `REMOTE_ADDR`. Detrás de un proxy es obligatorio configurarlo, y el proxy debe **sobrescribir** el header (no agregarle), porque se toma la primera IP.
- **Bloqueo por DNI** (`PublicIdentityAttemptLimiter`): 5 teléfonos incorrectos para un DNI existente lo bloquean 1 hora. Se responde el mismo 409 neutral **sin consultar la base**, así que quien ataca no distingue "bloqueado" de "teléfono incorrecto". Mientras está bloqueado no se suman fallos (vence 1 hora después del último). Una resolución exitosa reinicia la cuenta. Solo cuentan los rechazos por teléfono (B'); el email de otro usuario, el rol o un usuario inactivo no se pueden "adivinar" y no suman. La clave del cache es `public_identity_failures_{tenantId}_{sha256(dni)}`: el DNI solo es único por Tenant, así que el bloqueo de un Tenant no afecta a otro. `Throttle` (por IP) no lleva Tenant a propósito: es defensa de red.
- **Falla abierto**: si el cache activo es `DummyHandler` (p. ej. `writable/cache` sin permisos) el throttle y el bloqueo por DNI no limitan, sin ningún error. `Throttle` deja un `critical` en el log en cada request.
- **Costo asumido**: alguien puede bloquear a propósito a un paciente real durante 1 hora. El paciente puede iniciar sesión o llamar al consultorio.
- **CORS** solo en `/public/*` y solo para `cors.allowedOrigins` / `cors.allowedOriginsPatterns`. Sin orígenes configurados no se envía ningún header. Limitación conocida: `exposedHeaders` está vacío, así que con el SPA en otro dominio el navegador no puede leer `Retry-After` (ver `bitacora/pendiente/cors-exponer-retry-after.md`).

## Respuestas de error

| Código | Cuándo |
|---|---|
| `422` | Validación (`errors` por campo) |
| `409` | Identidad (B'/C/bloqueo, texto neutral); horario ocupado; médico no disponible; tope de 3 turnos |
| `423` | Otra solicitud en curso con el mismo DNI, o el profesional está procesando otra reserva (reintentar en segundos) |
| `429` | Throttle por IP, con `Retry-After` |

## Checklist de deploy

Los endpoints `/public/*` **no deben exponerse a internet** hasta completar el checklist de `bitacora/pendiente/deploy-*.md`. Resumen:
`app.trustedProxies`, `writable/cache` escribible, cache compartido si hay varias instancias, `cors.*`, migraciones probadas en una copia (UNIQUE de `users.dni` y `patients.user_id`), `EMAIL_*` y `reception.email`.

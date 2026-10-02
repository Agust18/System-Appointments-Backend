# PENDIENTES — BACKEND

Última actualización: 02/10/2026 (Fase 6, Parte A: documentación)

---

## 🔴 Bugs reales

Ninguno. ✅

---

## ✅ Cerrado (no volver a tocar)

### Booking / transacciones (30/09/2026)
- ✅ **B2 — Auditoría `transStart()` vs `transBegin()`**: todo `app/` usa `transBegin` + `transCommit` + `transRollback` manual (correcto: el rollback depende de excepciones de dominio, no de `transStatus`). Única inconsistencia: los 2 booking services capturaban `Exception` en vez de `\Throwable`; unificado (commit `8671dcb`). `MedicalCreatorService` y los seeders siguen el mismo patrón por grep, sin revisión línea a línea.
- ✅ **R6 — Booking atómico extraído**: nuevo Service delegado `AppointmentSlotLockService::run()` (lock `GET_LOCK` + transacción + validación de servicio + bloque + solapamiento + persistencia inyectada). `AppointmentBookingService` y `AppointmentUpdateBookingService` quedan como adaptadores finos. 22 tests de caracterización escritos antes del refactor (`tests/database/Appointment/`), idénticos antes y después. Suite: 242 tests OK. Endpoints verificados a mano: POST 200, PUT 200, solapamiento 409.

### Fixes posteriores a R6 (30/09/2026)
- ✅ **`DELETE /appointments/{id}` devolvía 500**: `AppointmentDeleterService` no inicializaba `$appointmentModel`. Fix de 1 línea + `AppointmentDeleterServiceTest` (borrado, no afecta otros turnos, pasado 403, inexistente 404). Commit `478d32e`.
- ✅ **`AppointmentServiceRequiredException` era código muerto**: el converter castea `service_id` NULL a 0. Guard ahora `=== null || <= 0`; turno legacy sin servicio devuelve 422 (antes 404 engañoso). El front ya lo maneja genéricamente (`parseApiError` → toast con `data.error`). Commit `648f8ed`.
- ✅ **Mensaje de ServiceRequired más claro** (30/09/2026): "seleccioná un servicio" en vez de "debe indicar serviceId".

### Bugs y fixes
- **Bug "estado inválido" al activar/desactivar** (30/09/2026): CI4 StrictRules `in_list` rechaza `bool` JSON nativo. Fix centralizado en `RequestValidator`: convierte bool de primer nivel del body a 0/1 antes de validar. Cubre MedicalSchedule, MedicalService, Medical, Service y User. Reglas actualizadas a `in_list[0,1,true,false]`.
  - ⚠️ **Nota para el futuro:** CI4 NO tiene regla `boolean`. No usarla. Para campos bool, convertir a 0/1 antes de validar.

---

## 🟡 Backend: fixes defensivos

| # | Fix | Esfuerzo |
|---|---|---|
| B1 | Chequeo del INSERT en todos los Models (Patient, Medical, Appointment, etc.) | 4-6 h |
| B3 | Mover transacciones a `Operation/` (Service delegado) | 2-3 h |

---

## 🟡 Refactors backend

| # | Refactor | Dónde | Esfuerzo | Impacto |
|---|---|---|---|---|
| R5 | Extraer `isDayOff()` a `MedicalDaysOffService` | `AppointmentAvailabilityBatchService` | 30 min | Bajo |

> **R5 — Detalle:** extraer `isDayOff()` de `AppointmentAvailabilityBatchService` a `MedicalDaysOffService`, inyectarlo en batch, single-date y Booking, e implementar la query real contra `medical_days_off`. Se hace **cuando se implemente la feature de días no laborables** (hoy `isDayOff()` devuelve `false`).

---

## 📊 Métricas del dashboard (backend)

- ✅ **Tasa de ausentismo** — cerrada (30/09/2026). `noshowRate` y `cancellationRate` en `GET /metrics/kpis`.
- ✅ **Especialidades más demandadas** — cerrada (30/09/2026). `GET /metrics/top-specialties`: top 5 por especialidad del médico (`appointments → medicals.specialty_id`; `services` no tiene `specialty_id`, no se tocó el schema). Excluye `cancelled`. El front oculta el widget si hay filtro de médico y no envía `medicalId`.
- ✅ **Turnos recientes** — cerrada (30/09/2026). `GET /metrics/recent-appointments`: últimos 10 por `updated_at DESC`, filtrados por `A.fecha` (rango + `medicalId`).
- ✅ **Refactor métricas** — cerrado (30/09/2026). Métricas no usa Projection+Converter. Es intencional: son agregaciones, no entidades. 1 método en `MetricsModel` + 1 Service (devuelve array) + 1 Controller por métrica; JSON verificado idéntico con diff antes/después. Excepción documentada en `CLAUDE.md`.
- ⏳ Alertas del sistema (post-MVP).

---

## 🟡 Deuda técnica backend

- **Doble `isActive` (users + medicals)**: un médico tiene `isActive` como usuario (puede loguearse) y como médico (puede atender). Revisar si la lógica de negocio es coherente en los 4 cruces posibles:
  - user activo + medical activo → normal
  - user activo + medical inactivo → puede loguearse pero no debería aparecer para asignar turnos
  - user inactivo + medical activo → no puede loguearse pero aparece en listas
  - user inactivo + medical inactivo → dado de baja total
  Confirmar que los listados y validaciones respetan esto.
  - **Ya cubierto en la reserva pública:** `PublicAppointmentCreateValidatorService` exige médico **y** usuario activos, y `GET /public/medicals/available` filtra `M.is_active = 1 AND U.is_active = 1`.
  - **Falta auditar** el resto de los listados autenticados: el filtro `isActive` de `MedicalModel` mira solo `M.is_active` (no el del usuario), así que un médico con usuario inactivo puede aparecer.

- **Migración al RequestValidator**: unificar la validación de requests que hoy está dispersa.

- **Micro-deuda**: el guard de `AppointmentUpdateBookingService` tiene `=== null || <= 0`, pero `=== null` es inalcanzable por tipos. Se puede simplificar a `<= 0`.

---

## 🔴 Reserva pública: checklist antes de exponerla a internet

La Fase 4 (throttle por IP + bloqueo por DNI) está **implementada**, pero los endpoints `/public/*` **NO deben exponerse a internet** hasta tildar todo esto:

- [x] **Suite completa en verde** tras la Fase 5 (02/10/2026): 520 tests, 1289 aserciones, 44 min. Cubre también las Fases 3 y 4 (en la Fase 4 eran 511 tests).
- [ ] **`app.trustedProxies`** (y `app.proxyHeader` si no es `X-Forwarded-For`) configurado si hay un proxy delante. Sin eso todos los usuarios comparten la IP del proxy y el límite de reservas pasa a ser de todos juntos. Y el proxy tiene que **sobrescribir** el header, no agregarle (nginx: `proxy_set_header X-Forwarded-For $remote_addr;`): CodeIgniter toma la primera IP de la lista, que un cliente puede falsear para evadir el throttle.
- [ ] **`writable/cache` escribible** por el usuario del servidor. Si no lo es, CodeIgniter cae al handler `dummy` (`backupHandler`) y el throttle y el bloqueo por DNI **dejan de limitar sin ningún error** (decisión: falla abierto). El filtro `Throttle` deja un `critical` en el log en cada request en ese caso: revisar los logs después del primer despliegue.
- [ ] **Varias instancias del backend → cache compartido (Redis).** Con `file` cada instancia cuenta por su cuenta y los límites se multiplican.
- [ ] **`cors.allowedOrigins` / `cors.allowedOriginsPatterns`** con los dominios de cada cliente (solo aplica a `/public/*`). Si el SPA y el API quedan en dominios distintos, el panel autenticado también necesita CORS: ver "CORS en rutas privadas" abajo.
- [ ] **Migraciones probadas en una copia de la base** (UNIQUE de `users.dni` y `patients.user_id`; los pre-checks abortan listando duplicados).
- [ ] **`EMAIL_*` configurado** (el paciente recibe la confirmación al email guardado).
- [ ] **`reception.email` configurado** (Fase 5): recepción recibe "Nuevo turno agendado" por cada reserva pública. Sin esa variable no se avisa (queda un `warning` en el log). `reception.emailEnabled = false` lo desactiva.

**Cómo limita** (valores aprobados; constantes en `Routes.php` y `PublicIdentityAttemptLimiter`):
- `GET /public/*`: 60 por minuto por IP. `POST /public/appointments`: 5 por minuto por IP. El Throttler es un *token bucket*: "60 por minuto" = ráfaga de 60 y después 1 por segundo; "5 por minuto" = ráfaga de 5 y después 1 cada 12 s. El preflight `OPTIONS` no gasta cupo.
- **Bloqueo por DNI:** 5 teléfonos incorrectos para un DNI existente lo bloquean 1 hora. Se responde el mismo 409 neutral sin consultar la base, así que quien ataca no distingue "bloqueado" de "teléfono incorrecto". Una reserva exitosa reinicia la cuenta. Solo cuentan los rechazos por teléfono (no el email de otro usuario, ni el rol, ni un usuario inactivo).
- **Costo asumido:** alguien puede bloquear a propósito a un paciente real durante 1 hora (el paciente puede iniciar sesión o llamar al consultorio).

**CORS en rutas privadas (no incluido):** hoy solo `/public/*` tiene CORS. Si en producción el SPA y el API quedan en dominios distintos, hay que: (1) sumar `Authorization` a `allowedHeaders` y `PUT`/`DELETE` a `allowedMethods` en `Cors.php`; (2) aplicar el filtro `cors` a todas las rutas; (3) una ruta `OPTIONS` global **fuera** del filtro `auth`, para que el preflight no pida token; (4) agregar el origen a `cors.allowedOrigins`. Si se sirven bajo el mismo dominio con un proxy inverso (`/api`), no hace falta.

---

## 🟡 Pendiente (reserva pública)

- **Flujo autenticado: `fecha`/`hora` sin largo exacto.** `AppointmentRequest` usa `valid_date[Y-m-d]` sin `exact_length`: `2026-10-5` y `2026-10-05` son el mismo día pero generan locks distintos (`appt_lock_{id}_{fecha}`), así que dos reservas simultáneas no se serializan. Además `hora` solo tiene `required` y el validador compara horas como strings (`9:30` vs `10:00` da mal). Pendiente (sin fase asignada): agregar `exact_length[10]` / `exact_length[5]` + `valid_date[H:i]`, como ya hace `PublicAppointmentRequest`. Tocar también `AppointmentUpdateRequest`. Es un cambio de contrato para el front autenticado: verificar que mande siempre `YYYY-MM-DD` y `HH:MM` con ceros.
- **Tope de turnos futuros "blando".** `PublicAppointmentCreatorService` cuenta los turnos activos del paciente antes de reservar, sin lock: dos solicitudes simultáneas del mismo paciente podrían pasar el tope por uno. Si molesta, resolver con un lock por paciente (como el de `public_identity_{dni}`).


- **CORS: exponer `Retry-After`.** `app/Config/Cors.php` tiene `exposedHeaders => []`. Con el SPA en otro dominio el navegador no puede leer el `Retry-After` del `429` (el wizard cae al fallback de 15 s). Fix de 1 línea: `'exposedHeaders' => ['Retry-After']` (y un caso en `CorsConfigTest`). Hacerlo antes de exponer `/public/*` con el front en otro dominio.
- **Agregar campo `code` a los `409` de `POST /public/appointments`.** Hoy el wizard distingue identidad / horario ocupado / médico no disponible / tope de turnos / fecha pasada **por el texto** del mensaje (`bookingErrors.js` en el front), así que reescribir un mensaje rompe el flujo en silencio. Con un `code` estable en el body de error (`{ "error": "...", "code": "IDENTITY_MISMATCH" }`) el front deja de depender del texto. Bajo esfuerzo: es un campo aditivo en `ApiExceptionHandler`/las excepciones tipadas. Al agregarlo, avisar al front para que priorice `code` sobre el texto.
- **Caso familiar (padre/hijo).** Un padre reserva para su hijo desde la reserva pública:
  - Hijo con **DNI propio** → sin problema (caso A).
  - Hijo con el **email del padre** → `409` neutral (caso C: el email ya es de otro usuario).
  - Hijo **sin DNI** → no puede reservar (el DNI es obligatorio).
  - **Solución actual:** la resuelve recepción a mano (alta del paciente con `POST /patients` y turno desde el panel). La solución definitiva son los perfiles de familia (ver "Features futuras").
---

## 🔵 Features futuras (no empezar sin pedido)

- **WhatsApp como canal de aviso alternativo al email.**
  - Costo: ~USD 0.005–0.05 por mensaje.
  - Esfuerzo: 8–12 h + configuración del proveedor.
  - Cuándo: con 10+ clientes.
  - Punto de entrada: `AppointmentNotificationService` (hoy envía por `EmailSenderService`; el `?callable $sender` ya desacopla el canal).
- **Perfiles de familia.** Un titular con varios pacientes a cargo, que reserva para sus hijos. Resuelve el "caso familiar" de arriba.
  - Esfuerzo: ~12–15 h.
  - Cuándo: cuando un cliente lo pida.

---

## 🟡 Suite lenta (~25 min)

Cada test de DB con `$refresh = true` re-migra todo `turnero_test` (unos 12 s por test). Correr con `docker exec turnero_app php vendor/bin/phpunit` (el host `db` solo resuelve dentro de Docker). `turnero_test` se reseteó el 30/09/2026 porque tenía `migrations` marcada sin las tablas.

**Regla de trabajo** (mientras tanto):
- Después de cada cambio: tests del archivo afectado.
- Después de cada commit: tests de la fase.
- Al final de la fase: suite completa.
- **No** correr la suite completa después de cada cambio.
- **Excepción:** si el cambio toca algo compartido (Service base, Converter, Model), correr la suite completa.

**Refactor futuro:** tests con transacciones que se revierten en vez de re-migrar (~4-6 h).

✅ **Suite completa tras la sub-fase 3.3 verificada** (01/10/2026): 430 tests, 756 aserciones, todo en verde (34 min). La 3.3 se commiteó sin esperarla; lo único compartido que toca es un método nuevo en `AppointmentModel` (`countActiveFutureByPatient`).

⚠️ Tests que rompen el `refresh` si no se cuidan (se descubrió en las fases 1-3):
- El `down()` de `ReplaceUsernameWithFirstLastName` reconstruye `users.username` (UNIQUE) desde nombre + apellido: los tests deben sembrar usuarios con **nombres únicos** (`uniqid()`).
- Los tests que dejan un índice borrado o filas que impidan revertir una migración (`uq_users_dni`, `uq_patients_user_id`) tienen que restaurarlo en `tearDown`.

---

## 🟢 Consistencia de código (backend)

- [ ] Refactor R5 (R6 ✅)
- [ ] Fixes defensivos B1, B3 (B2 ✅)
- [ ] Migración al RequestValidator

---

## Prioridad sugerida (backend)

1. **R5** — Extraer `isDayOff()` (30 min, preparación para días no laborables).
2. **B3** — Mover transacciones a `Operation/` (2-3 h). Con R6 hecho, parte ya vive en `AppointmentSlotLockService`.
3. **B1** — Chequeo del INSERT (4-6 h, cuando escales).

---

## Cómo usar este archivo

- Este archivo es **solo para el repo del backend**.
- Cuando retomes un item, copialo y pegalo en Claude Code para armar el prompt de arranque.
- Actualizá el archivo cuando cierres items.
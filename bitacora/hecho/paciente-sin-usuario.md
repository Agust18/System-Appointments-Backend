# Paciente sin usuario (ficha propia + /consulta)

- **Estado:** Fase 1 ✅ (2026-10-20) · Fase 2 ✅ (2026-10-21, backend + frontend) · Fase 3 ✅ (2026-10-20, backend + frontend)
- **Repo:** backend + frontend (`System-AppointmentsFrontend`)
- **Esfuerzo:** ~2,5–3,5 semanas (estimación gruesa)
- **Fecha:** 2026-10-20
- **Origen:** la lógica de "invitado vs registrado" y la doble escritura user+patient ensuciaron el código.

## Idea
El paciente deja de ser usuario: no hay cuenta, login ni password. Es una ficha en `patients` con nombre, apellido, email, DNI y teléfono. Consulta y cancela su turno por una vista pública `/consulta` con DNI + código (o el link del email). El staff (médicos, recepción, admin, plataforma) no cambia.

## Decisiones tomadas
- No hay pacientes reales en la base (solo el admin): sin backfill delicado, se pueden borrar cuentas de prueba.
- Código de `/consulta` **por turno** (no por paciente).
- El teléfono **deja de ser factor** al reservar: si el DNI existe se reutiliza la ficha **sin tocar sus datos**.
- **Sin plazo mínimo** para cancelar en v1 (configurable después).
- El rol 3 (paciente) **se borra** (`Role::NAMES`, `RolesSeeder`, `constants/role.js` del frontend).
- **Reprogramar** queda fuera de v1: se cancela y se reserva de nuevo.
- Verificación de email, email de bienvenida, reset de contraseña y login quedan para el staff.

## Fuera de alcance por ahora
- Confirmación por email del turno (`pending` con vencimiento). Si aparece abuso: versión condicional, solo cuando el DNI existe y el email tipeado difiere del guardado. Ver `caso-familiar-padre-hijo.md` (queda resuelto por esta tarea).
- El tope de 3 turnos futuros `pending`/`confirmed` por paciente se mantiene.

## Reglas de trabajo
- Una rama por fase, mergeada a `refactor/mejoras` (o a `master`) antes de empezar la siguiente.
- Correr solo los tests del módulo tocado (nunca la suite completa sin pedirlo; tarda ~44 min).
- Al terminar cada fase, marcarla acá y actualizar `CLAUDE.md` y `.claude/rules/*` si cambia lo documentado.

---

## Fase 1 — Paciente con identidad propia (la API pública sigue igual) ✅ TERMINADA
- [x] Migración: `patients` gana `first_name`, `last_name`, `email` (nullable), `dni`, `phone`.
- [x] `UNIQUE(tenant_id, dni)` en `patients`; email sin UNIQUE.
- [x] Soltar `patients.user_id` y su FK (hoy `ON DELETE CASCADE`).
- [x] `Patient` (entity), `PatientProjection`, DTOs y Converters con los campos nuevos (sin `userId`).
- [x] `PatientModel`: insert, proyecciones y búsqueda por nombre/DNI leen de `patients`, sin JOIN a `users`; borrar `findByUserId*`.
- [x] `AppointmentModel`: quitar el JOIN a `users U_pac` en las 5 queries (~l.237, 266, 356, 388, 441).
- [x] `MetricsModel`: quitar el JOIN a `users` (~l.139).
- [x] `PatientCreatorService`: un insert + `findOrCreateByDni()` (reutiliza sin modificar; carrera resuelta por el UNIQUE).
- [x] `PatientUpdaterService`: actualiza `patients` directo (sin `UserUpdaterService`).
- [x] Borrar `PatientGuestResolverService`; `PublicAppointmentCreatorService` usa `findOrCreateByDni()`.
- [x] `AppointmentCreateValidatorService`: sacar la validación de rol PATIENT y `PatientCannotBeMedicalException`.
- [x] Notificaciones y recordatorios: el email del paciente sale de `patients.email` (verificado con `AppointmentReminderServiceTest`).
- [x] Seeders (`DashboardDemoSeeder`/`Cleanup`) crean pacientes sin `users`.
- [x] Tests del módulo ajustados (Patient, Appointment, Public, Metrics, aislamiento por tenant) y verdes. 172 tests corridos; solo quedan 4 fallos previos y ajenos a esta tarea (ver abajo).

  Pendientes para dar el punto por cerrado:
  - ~~`PublicBookingEndpointsTest::testPhoneMismatchIsRejectedWith409`~~: reemplazado (el usuario confirmó que el teléfono ya no se verifica) por `testExistingDniIsReusedWithoutTouchingTheRecordWhateverTheTypedPhoneIs`. Verde.
  - ~~`DashboardSeedersTenantTest::testSeederWritesEveryRowInTheChosenTenant`~~: arreglado (el `insertID()` del médico se lee antes del `UPDATE users` en `DashboardDemoSeeder`). Verde.
  - Fallos previos y ajenos a esta tarea: 3 tests `testExcludeAppointmentId*` de disponibilidad (el request pasa un entero y el validador exige texto numérico) y `testWithoutConfiguredOriginsNoCorsHeadersAreSent` (depende de la config de CORS del entorno).

**Desvíos respecto del plan (adelantados desde la fase 2 porque sin `patients.user_id` no compilan/funcionan):**
- Borrados ya: `POST /patients/register`, `PatientPublicPostController`, `PatientPublicRegisterService`, `PatientSelfRegisterRequest`, `GET /patients/user/{id}` (+ `findByUser*`), `PatientDataRequest`.
- Ramas PATIENT ya sacadas de: `PermissionsService`, `AppointmentFilterBuilderService`, `AppointmentStatsService`, `PatientViewPermission` (ya no usa `ResolvesEntityByRouteSegment`; sigue usándolo `MedicalViewPermission`), `UserDeleterValidatorService`, `AppointmentCreateValidatorService`.
- `POST /patients` mantiene el formato `{user: {...}, patient: {...}}` (ignora `roleId`/`password`/`isActive`); `PatientRequest` ahora es plano (nombre, apellido, email, teléfono, DNI, fecha de nacimiento).
- `PatientAlreadyExistsException` ahora recibe el DNI (409). `down()` de la migración recrea usuarios sin cuenta para los pacientes sin `users` con su DNI.
- **Deuda a quitar en fase 2:** `PatientPostController` lee `$body['user']` y `$body['patient']` solo por compatibilidad con el frontend. Cuando el frontend mande el body plano (`firstName, lastName, email, phone, dni, birthDate`), leer `$body` directo y borrar esa adaptación (`$identity`).
- Quedan para la fase 2: filtros `appointmentPermission`/`appointmentCreatePermission`/`appointmentViewPermission`/`medicalViewPermission` (rama PATIENT), `AppointmentUpdateValidatorService`, `UserUpdateValidatorService`, `Role::PATIENT`, `User::createWithoutAccount/hasAccount`, `AccountActivationEmailTemplate`, excepciones sin uso y frontend.

## Fase 2 — Borrar lo del paciente-usuario ✅ TERMINADA
**Backend**
- [x] Borrar `POST /patients/register`, `PatientPublicPostController`, `PatientPublicRegisterService`, `PatientSelfRegisterRequest`.
- [x] Borrar `GET /patients/user/{id}` y `findByUser`.
- [x] `patientViewPermission` → filtro para admin/recepción/médico; sacar la rama PATIENT de `appointmentPermission`, `appointmentCreatePermission`, `appointmentViewPermission`, `medicalViewPermission` y `PermissionsService`.
- [x] Sacar ramas PATIENT de `AppointmentStatsService`, `AppointmentFilterBuilderService`, `AppointmentUpdateValidatorService`, `UserUpdateValidatorService`, `UserDeleterValidatorService`.
- [x] Borrar `AccountActivationEmailTemplate`, `User::createWithoutAccount/hasAccount/completeAsAccountless` y excepciones sin uso (`UserNotPatientException`, `PatientNotFoundExceptionByUserException`, `CannotCreateAppointmentForOtherPatientException`, `PatientCannotBeMedicalException`). **Decisión posterior:** también se borraron `User::hasAccount()`, `AccountActivationEmailTemplate`, la bifurcación de `SendPasswordResetEmailService` y el campo `hasAccount` de `UserResponse` (el mail de reset es único; `verifyPassword` comprueba `password === null`). Se quedan `PublicBookingIdentityException` y `PublicIdentityLockTimeoutException` se quedan para la fase 3.
- [x] Borrar `Role::PATIENT` (+ `NAMES`, `RolesSeeder`) y los `users` rol 3.
- [x] `PublicIdentityAttemptLimiter` queda solo para `/consulta` (fase 3).
- [x] Tests: borrar/reescribir los de registro, resolver y permisos de paciente (ver lista abajo). 120 tests de base de datos + 335 unitarios corridos; solo fallan los previos ajenos (CORS x3, `ServiceDuration` x2, hora con espacios, `excludeAppointmentId` x3).
- [x] Actualizar `CLAUDE.md`, `auth-rbac.md`, `email.md`, `public-booking.md`.

**Frontend**
- [x] Borrar `public/register/**`, `verifyEmail/**`, `PatientService.register`, rutas `/registro` y `/verify-email`.
- [x] Quitar `ROLES.PATIENT`, `userIsPatient`, `isPatient` de `useUserRole`, `Sidebar`, `AppointmentPage/Form/Wizard`, `StepProfessional`, `AppointmentColumns`, `AppointmentStatsRow`, `AppointmentListView`, `TableAction`.
- [x] `useAppointmentsPage`: borrar la resolución de paciente (`/patients/user/{id}`) y la cancelación de paciente.
- [x] `LoginModal` sin "Crear cuenta"; `Navbar` sin "Registrarse"; `TopBar` siempre a `/login`.
- [x] `BookingErrorPanel`/`StepPatient`: sacar el CTA "Iniciar sesión".
- [x] Badge "Sin cuenta" en `UserColumns`; email opcional de `UserForm`. **Decisión posterior:** el badge "Sin cuenta" también se borró (junto con `hasAccount`); el email de `UserForm` vuelve a ser obligatorio.
- [x] Ajustar `publicRoutes.test.jsx` y `Navbar.test.jsx`.

**Agregado en la fase 2 (acordado con el usuario)**
- [x] Se borró la verificación de email completa: `GET /verify-email`, `POST /resend-verification`, sus controllers/DTO/servicios (`Verify`, `Resend`, `SendVerification`, `EmailVerificationTokenService`), `EmailVerificationEmailTemplate` e `InvalidVerificationTokenException`. El staff nace verificado; la columna `users.is_verified` queda (siempre 1) y login/reset la siguen exigiendo.
- [x] `POST /patients` pasó a body plano (`firstName, lastName, email?, phone, dni, birthDate?`); se quitó la adaptación `{user, patient}`. Frontend: `toPatientPayload`, `PatientCreateForm` (alta sin contraseña/rol/activo).
- [x] Frontend: se quitó el flujo de alta de paciente desde Usuarios (`useQuickCreatePatient`); recepción solo crea profesionales desde ahí.
- [x] Migración `RemovePatientRoleAndUsers`: borra los `users` rol 3 y la fila 3 de `roles` (el id 3 no se reutiliza). `Role::inListRule()` arma el `in_list` de `roleId` desde `Role::NAMES`.
- Pendiente cosmético: CSS `.btn-register-public` y el `openLogin` del Navbar quedaron sin uso en el frontend.

## Fase 3 — `/consulta` v1 (consultar y cancelar) ✅ TERMINADA (backend + frontend, falta probar en un entorno real)
Decisiones tomadas al implementarla (acordadas con el usuario):
- Hash **SHA-256 simple**, sin clave secreta de la app: lo peor que pasa si se filtra la base es que cancelen un turno ajeno.
- El código se guarda solo como hash, así que **no se puede mostrar después**. Se entrega en claro una vez (respuesta del alta, pública y de panel, y email); si se pierde, recepción lo **regenera** (`POST /appointments/{id}/access-code`, invalida el anterior).
- Se pueden cancelar turnos `pending` **y** `confirmed` futuros (el caso de uso de `pending` está sin definir; se permiten ambos). Al cancelar se avisa al paciente **y a recepción**.
- La comparación del código es por SQL sobre el hash (no `hash_equals`): con bloqueo por DNI y throttle por IP no aporta.

- [x] Migración `AddAccessToAppointments`: `access_code_hash` + `access_token_hash` (UNIQUE). Código de 8 caracteres sin ambiguos + token de 32 bytes para el link.
- [x] `POST /public/appointments/lookup` (`cors` → `throttle:10,60`): `{dni, code}` o `{token}`.
- [x] `POST /public/appointments/cancel` (`cors` → `throttle:5,60`); dispara el aviso al paciente y a recepción.
- [x] Error único y neutral (`PublicBookingIdentityException`, 404), bloqueo por DNI con `PublicIdentityAttemptLimiter`. `PublicIdentityLockTimeoutException` se borró (nadie la usaba).
- [x] Emails con link `/consulta?t=...` y código (alta pública y alta de panel `confirmed`).
- [x] Panel: el alta devuelve `accessCode` (modal de una sola vez) y acción "Generar código de consulta" por turno.
- [x] Frontend: `public/consulta/` (form DNI + código, carga por `?t=`, cancelar con confirmación), código en la confirmación de la reserva.
- [x] Tests: `PublicAppointmentAccessTest` (16: hash, neutral, bloqueo, aislamiento por tenant, cancelación, regeneración), rutas en `PlatformRoutesTest`; frontend `ConsultaPage`, `consultaErrors`.
- [x] Documentado en `public-booking.md`, `auth-rbac.md`, `email.md`.
- Al desplegar: `php spark migrate` y `php spark db:seed RolesSeeder`; cada Tenant necesita `frontend_url` para que el link del email funcione (sin él, el mail sale solo con el código).

## Riesgos / puntos a vigilar
- Reservar con un DNI ajeno ya no tiene segundo factor: mitigan tope de 3 turnos, throttle por IP, no pisar datos y que recepción pueda cancelar.
- Tests de aislamiento por tenant a rehacer con `patients` propio: `UserPatientTenantIsolationTest`, `JoinTenantIntegrityTest`, `MetricsTenantIsolationTest`, `AppointmentTenantIsolationTest`.
- Tests que se borran o reescriben (~330): `PatientGuestResolverServiceTest`, `PatientPublicRegisterServiceTest`, `PatientCreatorServiceTest`, `UserPatientTenantIsolationTest`, `PublicBookingEndpointsTest`, `PermissionsServiceTest`, `PublicAppointmentCreatorServiceTest`, `AccountlessUserTest`, `PatientViewPermissionTest`, `PublicIdentityAttemptLimiterTest`, `AppointmentPermissionTest`, `PublicThrottleEndpointsTest`, `AppointmentViewPermissionTest`, `AppointmentCreatePermissionTest`. Además ~50 archivos tocan `Role::PATIENT`/`PatientModel`.
- Pacientes sin email: no reciben el código; recepción lo pasa por teléfono.
- Si el paciente pierde el código, solo queda llamar al consultorio.

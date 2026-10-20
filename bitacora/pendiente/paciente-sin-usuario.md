# Paciente sin usuario (ficha propia + /consulta)

- **Estado:** ⏳ Pendiente (aprobado, sin empezar)
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

## Fase 1 — Paciente con identidad propia (la API pública sigue igual)
- [ ] Migración: `patients` gana `first_name`, `last_name`, `email` (nullable), `dni`, `phone`.
- [ ] `UNIQUE(tenant_id, dni)` en `patients`; email sin UNIQUE.
- [ ] Soltar `patients.user_id` y su FK (hoy `ON DELETE CASCADE`).
- [ ] `Patient` (entity), `PatientProjection`, DTOs y Converters con los campos nuevos (sin `userId`).
- [ ] `PatientModel`: insert, proyecciones y búsqueda por nombre/DNI leen de `patients`, sin JOIN a `users`; borrar `findByUserId*`.
- [ ] `AppointmentModel`: quitar el JOIN a `users U_pac` en las 5 queries (~l.237, 266, 356, 388, 441).
- [ ] `MetricsModel`: quitar el JOIN a `users` (~l.139).
- [ ] `PatientCreatorService`: un insert + `findOrCreateByDni()` (reutiliza sin modificar; carrera resuelta por el UNIQUE).
- [ ] `PatientUpdaterService`: actualiza `patients` directo (sin `UserUpdaterService`).
- [ ] Borrar `PatientGuestResolverService`; `PublicAppointmentCreatorService` usa `findOrCreateByDni()`.
- [ ] `AppointmentCreateValidatorService`: sacar la validación de rol PATIENT y `PatientCannotBeMedicalException`.
- [ ] Notificaciones y recordatorios: el email del paciente sale de `patients.email`.
- [ ] Seeders (`DashboardDemoSeeder`/`Cleanup`) crean pacientes sin `users`.
- [ ] Tests del módulo ajustados (Patient, Appointment, Public, Metrics, aislamiento por tenant) y verdes.

## Fase 2 — Borrar lo del paciente-usuario
**Backend**
- [ ] Borrar `POST /patients/register`, `PatientPublicPostController`, `PatientPublicRegisterService`, `PatientSelfRegisterRequest`.
- [ ] Borrar `GET /patients/user/{id}` y `findByUser`.
- [ ] `patientViewPermission` → filtro para admin/recepción/médico; sacar la rama PATIENT de `appointmentPermission`, `appointmentCreatePermission`, `appointmentViewPermission`, `medicalViewPermission` y `PermissionsService`.
- [ ] Sacar ramas PATIENT de `AppointmentStatsService`, `AppointmentFilterBuilderService`, `AppointmentUpdateValidatorService`, `UserUpdateValidatorService`, `UserDeleterValidatorService`.
- [ ] Borrar `AccountActivationEmailTemplate`, `User::createWithoutAccount/hasAccount/completeAsAccountless` y excepciones sin uso (`UserNotPatientException`, `PatientNotFoundExceptionByUserException`, `CannotCreateAppointmentForOtherPatientException`, `PatientCannotBeMedicalException`).
- [ ] Borrar `Role::PATIENT` (+ `NAMES`, `RolesSeeder`) y los `users` rol 3.
- [ ] `PublicIdentityAttemptLimiter` queda solo para `/consulta` (fase 3).
- [ ] Tests: borrar/reescribir los de registro, resolver y permisos de paciente (ver lista abajo).
- [ ] Actualizar `CLAUDE.md`, `auth-rbac.md`, `email.md`, `public-booking.md`.

**Frontend**
- [ ] Borrar `public/register/**`, `verifyEmail/**`, `PatientService.register`, rutas `/registro` y `/verify-email`.
- [ ] Quitar `ROLES.PATIENT`, `userIsPatient`, `isPatient` de `useUserRole`, `Sidebar`, `AppointmentPage/Form/Wizard`, `StepProfessional`, `AppointmentColumns`, `AppointmentStatsRow`, `AppointmentListView`, `TableAction`.
- [ ] `useAppointmentsPage`: borrar la resolución de paciente (`/patients/user/{id}`) y la cancelación de paciente.
- [ ] `LoginModal` sin "Crear cuenta"; `Navbar` sin "Registrarse"; `TopBar` siempre a `/login`.
- [ ] `BookingErrorPanel`/`StepPatient`: sacar el CTA "Iniciar sesión".
- [ ] Badge "Sin cuenta" en `UserColumns`; email opcional de `UserForm`.
- [ ] Ajustar `publicRoutes.test.jsx` y `Navbar.test.jsx`.

## Fase 3 — `/consulta` v1 (consultar y cancelar)
- [ ] Migración: `appointments.access_code` (hash), generado al reservar. Código de 8 caracteres sin ambiguos + token largo para el link.
- [ ] `POST /public/appointments/lookup` (`cors` → `throttle:10,60`): `{dni, code}` o `{token}` → un solo turno.
- [ ] `POST /public/appointments/cancel` (`cors` → `throttle:5,60`): solo turnos `confirmed` y futuros; dispara `AppointmentCancelledNotificationService`.
- [ ] Validación con `hash_equals`, error único y neutral, bloqueo por DNI reutilizando `PublicIdentityAttemptLimiter`.
- [ ] Email de reserva (`PublicAppointmentCreatedNotificationService`) con link `/consulta?t=...` y código.
- [ ] Panel de recepción muestra el código para pacientes sin email.
- [ ] Frontend: página `public/consulta/` (form DNI + código, o carga por `?t=`; vista del turno con botón cancelar) reutilizando `publicApi.js`.
- [ ] Tests: aislamiento por tenant, límite por DNI, error neutral, cancelación.
- [ ] Documentar en `public-booking.md`.

## Riesgos / puntos a vigilar
- Reservar con un DNI ajeno ya no tiene segundo factor: mitigan tope de 3 turnos, throttle por IP, no pisar datos y que recepción pueda cancelar.
- Tests de aislamiento por tenant a rehacer con `patients` propio: `UserPatientTenantIsolationTest`, `JoinTenantIntegrityTest`, `MetricsTenantIsolationTest`, `AppointmentTenantIsolationTest`.
- Tests que se borran o reescriben (~330): `PatientGuestResolverServiceTest`, `PatientPublicRegisterServiceTest`, `PatientCreatorServiceTest`, `UserPatientTenantIsolationTest`, `PublicBookingEndpointsTest`, `PermissionsServiceTest`, `PublicAppointmentCreatorServiceTest`, `AccountlessUserTest`, `PatientViewPermissionTest`, `PublicIdentityAttemptLimiterTest`, `AppointmentPermissionTest`, `PublicThrottleEndpointsTest`, `AppointmentViewPermissionTest`, `AppointmentCreatePermissionTest`. Además ~50 archivos tocan `Role::PATIENT`/`PatientModel`.
- Pacientes sin email: no reciben el código; recepción lo pasa por teléfono.
- Si el paciente pierde el código, solo queda llamar al consultorio.

# Sistema de Email

Pipeline: `app/Email/EmailSenderService` → `app/Models/Email/EmailModel` → PHPMailer.

Para enviar: instanciar `EmailRequest` (destinatario, asunto, cuerpo HTML) y
llamar a `EmailSenderService::send()`. Si falla, lanza
`EmailCannotBeSendedException` (500).

Templates HTML: `UserEmailTemplateRenderer` y `UserEmailTemplateRendererPassword`
en `app/Email/User/`.

## Verificación de email

El flag `isVerified` (parámetro `$isVerified` en los Creator Services) decide
si el usuario nace verificado y si se dispara el email de verificación
(`SendVerificationEmailService`, `app/Services/auth/`).

| Endpoint | Controller | `isVerified` | Envía email |
|---|---|---|---|
| `POST /patients/register` | `PatientPublicPostController` | `false` | Sí |
| `POST /public/appointments` | `PublicAppointmentPostController` | n/a: el usuario nace **sin cuenta** (`password` NULL) | No (solo el aviso del turno, abajo) |
| `POST /patients` (admin/recepción) | `PatientPostController` | `true` | No |
| `POST /medicals` (admin/recepción) | `MedicalPostController` | `true` | No |

`UserCreatorService::create()` marca al `User` como verificado
(`$user->markAsVerified()`) cuando `$isVerified` es `true`, antes del insert.
`PatientCreatorService::create()` dispara el email de verificación fuera de la
transacción, solo cuando `!$isVerified`.

`POST /patients/register` siempre responde `201` y siempre envía el email de verificación.
Si el DNI es de un usuario sin cuenta (mismo email guardado, rol paciente, activo),
`PatientPublicRegisterService` fija la contraseña del body (solo la contraseña; nombre y
teléfono no se tocan), crea el paciente si falta y envía el mismo email de verificación
(el usuario queda `is_verified = 0` y no puede iniciar sesión hasta verificar).

### Por qué es intencional

Solo el self-register (`/patients/register`) necesita verificar el email
porque nadie validó los datos ingresados. Los usuarios creados por
admin/recepción (tanto `Patient` como `Medical`) ya fueron validados por el
consultorio, así que nacen verificados y no requieren el paso extra.

## Configuración por Tenant (`TenantEmailSettingsResolverService`)

`app/Services/Tenant/TenantEmailSettingsResolverService` es el único lugar que lee `tenant_settings` para emails (Tenant del `TenantContext`; una instancia por Tenant). Los Services no leen `env()` para esto.

- **`frontendUrl()`**: `frontend_url` del Tenant + `/verify-email?token=` o `/reset-password?token=`. **Sin respaldo en el `.env`**: si falta, `TenantFrontendUrlNotConfiguredException` (500) y se resuelve **antes** de generar el token (no queda huérfano). `PatientCreatorService::create()` (solo `!$isVerified`) y `PatientPublicRegisterService::register()` llaman antes a `assertFrontendUrlConfigured()`, así que sin `frontend_url` responden 500 sin crear usuario ni paciente.
- **`replyTo()`**: `reply_to_email` → `reception_email` → ninguno (sin header; el cliente responde al From = `EMAIL_FROM`). Va en **todos** los emails del Tenant (verificación, reset, avisos de turno, recordatorios). `reception_email` cuenta aunque el aviso a recepción esté desactivado. `EmailRequest::$replyTo` / `EmailModel::addReplyTo()`; un valor inválido se ignora.
- El SMTP y el From siguen siendo únicos de la plataforma (`EMAIL_*`).
- Un Tenant nuevo nace con `frontend_url` NULL: hay que cargarlo con `PUT /platform/tenants/{id}/settings` antes de que sus usuarios se registren o pidan reset.
- Tests: los que llegan a esos envíos siembran la config con `Tests\Support\Database\SeedsTenantEmailSettings`.

## Avisos de turnos (`AppointmentNotificationService`)

| Método | Quién lo llama | Emails |
|---|---|---|
| `notifyOnCreate()` | `AppointmentCreatorService` (admin/recepción/paciente logueado) | Paciente: "Tu turno fue confirmado" (solo si nace `confirmed`) |
| `notifyOnPublicCreate()` | `PublicAppointmentCreatorService` (`POST /public/appointments`) | Paciente: "Tu turno fue creado y confirmado". Recepción: "Nuevo turno agendado" (fecha, hora, paciente, teléfono, médico, servicio) |

Un turno público se identifica por el servicio que lo crea, no por un campo ni por el status
(un turno de admin también puede nacer `confirmed`). El email al paciente va al email
**guardado** en su usuario, no al que se tipeó en el formulario. Flujo completo en `public-booking.md`.

Config de recepción en `tenant_settings` (por Tenant, ya no en el `.env`), vía `TenantEmailSettingsResolverService`:
- `reception_email`: destinatario. Si falta, no se envía y se loguea un `warning`.
- `reception_email_enabled`: `false` desactiva el aviso (por defecto `true`).

Cada envío pasa por `sendSafely()`: un fallo se loguea y no corta la reserva ni el otro email.
El constructor acepta un `?callable $sender` como punto de observación en tests (por defecto usa `EmailSenderService`).


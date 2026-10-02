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
`PatientCreatorService::create()` es el único que además dispara el email:
lo hace fuera de la transacción, solo cuando `!$isVerified`.

### Por qué es intencional

Solo el self-register (`/patients/register`) necesita verificar el email
porque nadie validó los datos ingresados. Los usuarios creados por
admin/recepción (tanto `Patient` como `Medical`) ya fueron validados por el
consultorio, así que nacen verificados y no requieren el paso extra.

## Avisos de turnos (`AppointmentNotificationService`)

| Método | Quién lo llama | Emails |
|---|---|---|
| `notifyOnCreate()` | `AppointmentCreatorService` (admin/recepción/paciente logueado) | Paciente: "Tu turno fue confirmado" (solo si nace `confirmed`) |
| `notifyOnPublicCreate()` | `PublicAppointmentCreatorService` (`POST /public/appointments`) | Paciente: "Tu turno fue creado y confirmado". Recepción: "Nuevo turno agendado" (fecha, hora, paciente, teléfono, médico, servicio) |

Un turno público se identifica por el servicio que lo crea, no por un campo ni por el status
(un turno de admin también puede nacer `confirmed`). El email al paciente va al email
**guardado** en su usuario, no al que se tipeó en el formulario. Flujo completo en `public-booking.md`.

Config de recepción en el `.env`:
- `reception.email`: destinatario. Si falta, no se envía y se loguea un `warning`.
- `reception.emailEnabled`: `false` desactiva el aviso (por defecto `true`).

Cada envío pasa por `sendSafely()`: un fallo se loguea y no corta la reserva ni el otro email.
El constructor acepta un `?callable $sender` como punto de observación en tests (por defecto usa `EmailSenderService`).


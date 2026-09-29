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
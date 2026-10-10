# FRONTEND_URL por Tenant en los emails de verificación y de reset

- **Estado:** RESUELTO (08/10/2026, Paso 4.2). Los links usan `tenant_settings.frontend_url` (`TenantEmailSettingsResolverService`), sin respaldo en el `.env`: sin él, 500 antes de generar el token y antes de crear usuario en el registro. Pendiente menor: los avisos de turno y recordatorios no arman URLs absolutas (revisado). El texto de abajo es el historial.
- **Repo:** backend (y frontend, que debe leer el Tenant del subdominio)
- **Esfuerzo:** medio
- **Fecha:** 06/10/2026
- **Origen:** revisión del Grupo 3 del multitenant

## Problema
Los emails de verificación de cuenta y de restablecer contraseña arman el link con un único `FRONTEND_URL` (`.env`). Con multitenant, el usuario de un Tenant que recibe ese email hace clic y llega a un host que no es el de su Tenant. La SPA saca el `X-Tenant` del subdominio; sin él, en producción la API responde 400 (el fallback `demo` solo existe fuera de producción) y el link no funciona. En desarrollo no se nota porque el fallback lo tapa.

Además, el token se guarda en `user_verification_tokens` con `tenant_id` (Grupo 3): si el link llegara al Tenant equivocado, `findByToken` no lo encontraría.

## Qué hay que hacer
- Calcular la URL del frontend por Tenant (`{slug}.{dominio}`; el dominio base pasa a ser la configuración de plataforma y el slug sale de `TenantContext`), en `SendVerificationEmailService` y en el flujo de reset de contraseña.
- Dejar de usar `FRONTEND_URL` como único valor (o conservarlo solo como dominio base de la plataforma).
- Test: el link del email de un usuario del Tenant X apunta al subdominio de X.
- Revisar cualquier otro lugar que arme URLs absolutas con `FRONTEND_URL` (avisos de turnos, recordatorios).

Relacionado: `multitenant-plan.md` (Paso 4: configuración por Tenant), `adr/002-tenant-por-subdominio-y-header.md`.

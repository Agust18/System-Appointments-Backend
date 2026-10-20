# Deploy: reception.email configurado

> **HECHO (20/10/2026).** La variable `reception.email` del `.env` ya no existe: el destinatario del aviso a recepción es `tenant_settings.reception_email`, por Tenant (`TenantEmailSettingsResolverService::receptionEmail()`), y `reception_email_enabled` reemplaza a `reception.emailEnabled`. Se carga con `PUT /platform/tenants/{id}/settings` (`receptionEmail`, `receptionEmailEnabled`). Un Tenant nuevo nace con `reception_email` NULL: cargarlo es un paso operativo del alta, no un ítem de deploy. Sin él no se avisa y queda un `warning` en el log. El resto es el texto original.

- **Repo:** backend
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

- [x] **`reception.email` configurado** (Fase 5): recepción recibe "Nuevo turno agendado" por cada reserva pública. Sin esa variable no se avisa (queda un `warning` en el log). `reception.emailEnabled = false` lo desactiva.

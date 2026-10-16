# Contacto público por Tenant — primera pasada (backend)

- **Estado:** hecho (16/10/2026)
- **Contrato para el frontend:** [contrato-contacto-publico.md](contrato-contacto-publico.md)
- **Segunda pasada (mapa y redes):** [../pendiente/contacto-publico-por-tenant.md](../pendiente/contacto-publico-por-tenant.md)

## Qué se hizo
- Migración `AddContactToTenantSettings`: `contact_phone`, `contact_whatsapp`, `contact_email`, `address`, `opening_hours` (nullable, sin backfill) y `landing_enabled TINYINT(1) NOT NULL DEFAULT 1`.
- `TenantSettings` (constructor, `defaultsFor`, `modify`, getters), `TenantSettingsModel` y `PrimitiveToTenantSettingsConverter`.
- `TenantSettingsUpdateRequest` (`sanitize` normaliza el WhatsApp a solo dígitos; `rules()` fija longitudes y email) y `TenantSettingsUpdateValidatorService` (teléfono, WhatsApp 8–15 dígitos, `landingEnabled` no nulo).
- `GET/PUT /platform/tenants/{id}/settings` devuelven y editan los campos nuevos.
- `GET /tenant/config` devuelve `landingEnabled` y el bloque `contact` (`TenantContactResponse`); nunca `reception_email`, `reply_to_email` ni `frontend_url`.
- `CLAUDE.md`, `platform-admin.md` y `glosario.md` actualizados.
- Tests: `TenantSettingsPlatformTest` y `TenantSettingsTest` (PUT parcial, `null` limpia, validación, aislamiento, sin fuga de datos internos). Módulo afectado: 118 tests OK. No se corrió la suite completa.

## Decisiones
- **El alta de Tenant no pide contacto:** nace todo en `NULL` y `landing_enabled = 1`; se carga desde el panel.
- **`landing_enabled` es un flag, no el sistema de módulos.** El servidor no bloquea nada: la landing es del frontend. Si se hacen módulos, pasa a ser el módulo `landing`.

## Deploy
`php spark migrate` en cada ambiente. Sin backfill.

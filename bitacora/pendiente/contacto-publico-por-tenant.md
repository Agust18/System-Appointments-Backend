# Contacto público por Tenant (teléfono, WhatsApp, dirección, horarios, mapa, redes)

- **Estado:** pendiente
- **Origen:** con un único build multi-tenant (ADR 002) el frontend no puede tener el contacto en `VITE_CLINIC_*` (se fija en build: todas las clínicas mostrarían el mismo). Debe venir de la API.
- **Lado frontend:** `System-AppointmentsFrontend/bitacora/pendiente/contacto-clinica-a-tenant-settings.md`. Este archivo es el lado backend y va primero.

## Estado actual verificado (13/10/2026)
- `tenant_settings` tiene: `tenant_id`, `name`, `reply_to_email`, `frontend_url`, `reception_email`, `reception_email_enabled`, `primary_color`, `secondary_color` (+ timestamps). **No hay ningún campo de contacto público.**
- `GET /tenant/config` devuelve `slug`, `name`, `primaryColor`, `secondaryColor` (`TenantConfigResponse`).
- `reception_email` es un email **interno** (avisos al consultorio); `reply_to_email` es el Reply-To de los emails. Ninguno sirve como contacto público.

## Qué se agrega (solo aditivo: no se quita nada de lo hecho)
Columnas nullable en `tenant_settings` (migración nueva, mismo patrón y guardas `columnExists` que `AddBrandingToTenantSettings`):

| Columna | Notas |
|---|---|
| `contact_phone` | para mostrar y `tel:` |
| `contact_whatsapp` | normalizar a solo dígitos con código de país |
| `contact_email` | público; **distinto** de `reception_email` y `reply_to_email` |
| `address` | |
| `opening_hours` | texto libre |
| `map_embed_url` | solo `https://` |
| `social_facebook`, `social_instagram`, `social_twitter`, `social_linkedin` | URLs `https://` |

## Pasos
1. Migración `AddContactToTenantSettings` (up/down con `columnExists`). Sin backfill: `NULL` = no cargado.
2. Entity `TenantSettings`: constructor, `defaultsFor`, `modify` (campo ausente = sin cambio; `null` limpia) y getters. Ojo: `defaultsFor` y los `new self(...)` usan parámetros posicionales.
3. `TenantSettingsModel` (allowedFields y mapeo) y `PrimitiveToTenantSettingsConverter`.
4. `TenantSettingsUpdateRequest` + `TenantSettingsUpdateValidatorService`: longitudes, `https` en URLs, email válido. Mismo estilo que los campos actuales.
5. `TenantSettingsResponse` y `TenantSettingsToTenantSettingsResponseConverter` (`GET/PUT /platform/tenants/{id}/settings`).
6. `GET /tenant/config`: agregar un bloque `contact` al DTO y al converter. Es **aditivo**: no cambia lo que ya devuelve (si `quitar-branding-de-colores.md` ya se hizo, queda `slug`, `name` y `contact`).
7. Actualizar la regla de `CLAUDE.md` ("solo devuelve `slug`, `name` y el branding…") para incluir el contacto público, manteniendo "nunca emails internos ni `frontend_url`".
8. Tests: extender `TenantSettingsPlatformTest` y `TenantSettingsTest` (PUT parcial, `null` limpia, validación https) y asegurar que `GET /tenant/config` **no** expone `reception_email`, `reply_to_email` ni `frontend_url`. Correr la suite completa.
9. Docs: `.claude/rules/platform-admin.md`, `bitacora/glosario.md` (Configuración del Tenant) y un `hecho/contrato-contacto-publico.md` para el frontend.

## Fuera de alcance (decisión explícita)
- Quitar `primary_color`/`secondary_color`: tarea aparte, [quitar-branding-de-colores.md](quitar-branding-de-colores.md). **Hacerla antes que esta** (toca los mismos archivos). Este cambio no los toca.
- Logo (`logo-por-tenant.md`) y `timezone` (`multitenant-plan.md`): siguen en sus pendientes.

## Pregunta abierta
- ¿El alta de Tenant (`POST /platform/tenants`) pide algún dato de contacto, o nace todo en `NULL` y se carga desde el panel? Propuesta: `NULL`.

# Quitar el branding de colores (`primary_color` / `secondary_color`)

- **Estado:** hecho (13/10/2026)
- **Origen:** el frontend dejó de usar los colores del tenant (commit `fa1bccf` del repo frontend). Pisaban solo 4 tokens CSS y rompían el contraste.

## Cambio de contrato (para el frontend)

| Endpoint | Antes | Ahora |
|---|---|---|
| `GET /tenant/config` | `{slug, name, primaryColor, secondaryColor}` | `{slug, name}` |
| `GET /platform/tenants/{id}/settings` | incluía `primaryColor`, `secondaryColor` | sin esas claves |
| `PUT /platform/tenants/{id}/settings` | aceptaba y validaba `#RRGGBB` | **ignora** `primaryColor`/`secondaryColor` (sin 422 ni 500) |

Caso borde: un PUT cuyo body tenga **solo** claves de color queda sin campos válidos y responde `422` (`body`: "Enviá al menos un campo para modificar."). Un PUT con `name` + colores guarda el nombre e ignora los colores. El frontend actual no los envía.

## Qué se hizo (commits separados)
1. Respuestas: `TenantConfigResponse`, `TenantSettingsResponse` y sus converters.
2. Request: `TenantSettingsUpdateRequest` (`FIELDS`, `CLEARABLE`, reglas, mensajes) y `TenantSettings::modify`. Las claves desconocidas ya se descartaban en `sanitize()` (`array_intersect_key`).
3. Entity, `PrimitiveToTenantSettingsConverter` y SQL de `TenantSettingsModel`.
4. Migración `2026-10-13-100000_DropBrandingFromTenantSettings`: `DROP COLUMN` con guarda `columnExists`; `down()` recrea ambas `char(7) DEFAULT NULL` (vacías: **los valores cargados se pierden**). No se editó `AddBrandingToTenantSettings`.
5. Tests: se borró la sección de colores de `TenantSettingsPlatformTest`; se agregaron tests de claves de color ignoradas y de `GET /tenant/config` con forma exacta `{slug, name}`.

## Para el deploy
La migración es destructiva. Probarla en una copia de la base antes de producción (ver `pendiente/deploy-migraciones-en-copia-de-base.md`).

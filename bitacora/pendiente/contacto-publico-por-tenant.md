# Contacto público por Tenant — segunda pasada (mapa y redes)

- **Estado:** pendiente (la primera pasada está hecha: [hecho/contacto-publico-por-tenant.md](../hecho/contacto-publico-por-tenant.md))
- **Lado frontend:** `System-AppointmentsFrontend/bitacora/pendiente/contacto-clinica-a-tenant-settings.md` (formulario del panel y consumo de `/tenant/config`).
- No bloquea al primer cliente.

## Qué se agrega (solo aditivo)
Columnas nullable en `tenant_settings`, migración nueva con guardas `columnExists` (como `AddContactToTenantSettings`):

| Columna | Notas |
|---|---|
| `map_embed_url` | solo `https://` |
| `social_facebook`, `social_instagram`, `social_twitter`, `social_linkedin` | URLs `https://` |

## Pasos
Mismos que la primera pasada, para estos campos:
1. Migración (sin backfill: `NULL` = no cargado).
2. Entity `TenantSettings` (constructor posicional, `defaultsFor`, `modify`, getters), Model y `PrimitiveToTenantSettingsConverter`.
3. `TenantSettingsUpdateRequest` + `TenantSettingsUpdateValidatorService`: `https` obligatorio, longitud, sin `javascript:`. Para `map_embed_url` decidir si se restringe el host (p. ej. Google Maps), porque se usará en un iframe.
4. `TenantSettingsResponse` y su converter; ampliar el bloque `contact` de `TenantConfigResponse` (aditivo).
5. Tests solo de lo crítico: validación `https`, `null` limpia, aislamiento.
6. Docs: `platform-admin.md`, `glosario.md` y ampliar `hecho/contrato-contacto-publico.md`.

## Fuera de alcance
- Logo (`logo-por-tenant.md`) y `timezone` (`multitenant-plan.md`): siguen en sus pendientes.

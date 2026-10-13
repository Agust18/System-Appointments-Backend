# Quitar el branding de colores (`primary_color` / `secondary_color`)

- **Estado:** pendiente
- **Origen:** el frontend dejó de usar los colores del tenant (13/10/2026, commit `fa1bccf` del repo frontend). Pisaban solo 4 tokens CSS y rompían el contraste. El backend sigue guardándolos, validándolos y devolviéndolos: hoy son código muerto.
- **Relacionado:** `contacto-publico-por-tenant.md` toca los mismos archivos (entity, DTOs, converters, tests). **Hacer este primero** para evitar conflictos.

## Estado actual verificado
Archivos que mencionan el branding de colores:
- `app/Database/Migrations/2026-10-08-100000_AddBrandingToTenantSettings.php` (no se edita: es historia)
- `app/Entity/Tenant/TenantSettings.php`
- `app/Models/Tenant/TenantSettingsModel.php`
- `app/Converter/Tenant/PrimitiveToTenantSettingsConverter.php`
- `app/Converter/Tenant/TenantSettingsToTenantConfigResponseConverter.php`
- `app/Converter/Platform/TenantSettingsToTenantSettingsResponseConverter.php`
- `app/Dto/Request/Platform/TenantSettingsUpdateRequest.php`
- `app/Dto/Response/Platform/TenantSettingsResponse.php`
- `app/Dto/Response/Tenant/TenantConfigResponse.php`
- `tests/database/Platform/TenantSettingsPlatformTest.php`, `tests/database/Tenant/TenantSettingsTest.php`
- `.claude/rules/platform-admin.md`, `bitacora/glosario.md`, `bitacora/pendiente/logo-por-tenant.md`, `CLAUDE.md`
- Revisar también `TenantSettingsUpdateValidatorService` (validación `#RRGGBB`).

## Orden de ejecución (cada paso deja el sistema funcionando)
0. **Precondición**: el build nuevo del frontend (sin colores) ya está desplegado. Un frontend viejo podría mandar los colores en el PUT.
1. **Dejar de exponerlos**: sacar `primaryColor`/`secondaryColor` de `TenantConfigResponse` (`GET /tenant/config`) y de `TenantSettingsResponse`, y de sus converters.
2. **Dejar de aceptarlos**: sacar los campos de `TenantSettingsUpdateRequest`, de `TenantSettings::modify` y de la validación. Decidir qué hace el PUT si llega `primaryColor`: lo normal es ignorar claves desconocidas (verificar cómo lo trata hoy el request).
3. **Entity/Model/Converter**: quitar propiedades, getters, constructor (`defaultsFor` y todos los `new self(...)` son posicionales: revisar cada uno), `allowedFields` y el mapeo.
4. **Migración nueva** `DropBrandingFromTenantSettings`: `DROP COLUMN` de ambas con guardas `columnExists`; `down()` las recrea como `char(7) DEFAULT NULL` (mismo orden: después de `reception_email_enabled`). **Destructiva**: se pierden los valores cargados; el frontend ya los ignora. Probar la migración en una copia de la base (ver `deploy-migraciones-en-copia-de-base.md`).
5. **Tests**: borrar/ajustar los de colores en los dos archivos; agregar un test de que `GET /tenant/config` ya no trae las claves de color. El resto de los tests de settings debe seguir verde. Correr la suite completa.
6. **Docs**: `CLAUDE.md` (regla de `GET /tenant/config`: "solo devuelve `slug` y `name`"), `.claude/rules/platform-admin.md`, `glosario.md` (quitar `primary_color` de "Configuración del Tenant"), `logo-por-tenant.md` (ya no "El branding hecho cubre colores") y un `hecho/` con el cambio de contrato para el frontend.

## Criterio de cierre
- `grep -ri "primary_color\|primaryColor\|secondary_color\|secondaryColor"` solo encuentra la migración histórica y la de borrado.
- Suite completa en verde.
- `GET /tenant/config` devuelve `{slug, name}` (más el bloque `contact` cuando se haga el otro pendiente).

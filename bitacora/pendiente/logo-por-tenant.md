# Logo por Tenant

- **Estado:** pendiente. El branding hecho (08/10/2026) cubre solo `primary_color` y `secondary_color`.
- **Qué falta:** un logo por Tenant en `GET /tenant/config` (por ejemplo `logoUrl`).
- **Por qué no está:** requiere subida de archivos. Hay que decidir dónde se guardan (disco, S3), qué formatos y tamaño máximo se aceptan (SVG implica sanear contra XSS), cómo se sirven (URL pública, cache) y quién los sube (solo el panel de plataforma, igual que el resto de la config).
- **Dónde encaja:** una columna `logo_url` en `tenant_settings`, editable con `PUT /platform/tenants/{id}/settings` o con un endpoint propio de subida en `/platform/*`.

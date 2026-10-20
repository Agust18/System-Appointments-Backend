# Contrato: contacto público por Tenant y `landingEnabled`

Primera pasada (teléfono, WhatsApp, email público, dirección, horarios). Mapa y redes: pendiente.
El alta de Tenant no pide contacto: nace todo en `null` y se carga desde el panel. `landingEnabled` nace en `true`.

## `GET /tenant/config` (público, sin login)
```json
{
  "slug": "clinica-sur",
  "name": "Clínica Sur",
  "landingEnabled": true,
  "contact": {
    "phone": "+54 (11) 4000-1234",
    "whatsapp": "5491155551234",
    "email": "info@sur.test",
    "address": "Av. Siempreviva 742",
    "openingHours": "Lun a Vie 8 a 18"
  }
}
```
- Cada campo de `contact` puede ser `null` (no cargado): el frontend oculta ese dato.
- `whatsapp` va solo con dígitos y código de país (`https://wa.me/{whatsapp}`); `phone` es texto para mostrar (`tel:`).
- `contact.email` es el email **público**; nunca se exponen `receptionEmail`, `replyToEmail` ni `frontendUrl`.
- `landingEnabled = false`: el servidor no corta nada (la landing es del frontend); el frontend redirige `/` al login/reserva y oculta Inicio/Contacto.

## `GET` / `PUT /platform/tenants/{id}/settings`
El GET devuelve además: `contactPhone`, `contactWhatsapp`, `contactEmail`, `address`, `openingHours`, `landingEnabled`.
El PUT acepta los mismos nombres. Campo ausente = sin cambio; `null` o `""` limpia los de contacto; `landingEnabled` es bool y no se limpia.

| Campo | Regla | 
|---|---|
| `contactPhone` | máx. 50; solo dígitos, espacios, `+ - ( ) .` |
| `contactWhatsapp` | se normaliza a solo dígitos; 8 a 15 dígitos |
| `contactEmail` | email válido, máx. 255 |
| `address` | máx. 255 |
| `openingHours` | texto libre, máx. 500 |
| `landingEnabled` | `true`/`false` |

Errores de validación: `422` con `errors` por campo.

# CORS: exponer Retry-After

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** 1 línea + 1 caso de test
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Detalle

- **CORS: exponer `Retry-After`.** `app/Config/Cors.php` tiene `exposedHeaders => []`. Con el SPA en otro dominio el navegador no puede leer el `Retry-After` del `429` (el wizard cae al fallback de 15 s). Fix de 1 línea: `'exposedHeaders' => ['Retry-After']` (y un caso en `CorsConfigTest`). Hacerlo antes de exponer `/public/*` con el front en otro dominio.

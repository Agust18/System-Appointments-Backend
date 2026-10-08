# CORS en rutas privadas (no incluido)

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

**CORS en rutas privadas (no incluido):** hoy solo `/public/*` tiene CORS. Si en producción el SPA y el API quedan en dominios distintos, hay que: (1) sumar `Authorization` a `allowedHeaders` y `PUT`/`DELETE` a `allowedMethods` en `Cors.php`; (2) aplicar el filtro `cors` a todas las rutas; (3) una ruta `OPTIONS` global **fuera** del filtro `auth`, para que el preflight no pida token; (4) agregar el origen a `cors.allowedOrigins`. Si se sirven bajo el mismo dominio con un proxy inverso (`/api`), no hace falta.

## Actualización (07/10/2026, Platform Admin)
Este archivo **sigue vigente para las rutas de tenant**. El Paso 6 del Platform Admin solo resuelve CORS para `/platform/*`: perfil `$platform` en `Cors.php` (usado con el filtro `cors:platform`), con `PUT` y `Authorization` y sin `X-Tenant`, y los mismos `cors.allowedOrigins` / `cors.allowedOriginsPatterns` que `/public/*`. El perfil `default` no cambió. Si el panel va a otro dominio, hace falta una lista de orígenes aparte para `$platform`.

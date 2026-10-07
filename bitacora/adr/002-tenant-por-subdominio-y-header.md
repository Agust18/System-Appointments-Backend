# ADR 002: Tenant identificado por subdominio, enviado a la API en el header `X-Tenant`

Estado: aceptado (2026-10-05)

## Decisión
Cada Tenant vive en `{slug}.tusturnos.com`. La SPA lee el subdominio y manda `X-Tenant: {slug}` a la API. La API resuelve el Tenant desde ese header; no depende del dominio del frontend. Un Filter nuevo carga el Tenant y rechaza con 403 si está suspendido. El CORS se resuelve con patrón de subdominio.

## Por qué
- Marca propia por Tenant y camino abierto a dominios propios.
- DNS y certificado wildcard en Cloudflare, sin devops.
- El header desacopla la API del dominio del frontend.

## Alternativa descartada
Slash (`tusturnos.com/{slug}`): más fácil de operar, pero peor para marca y para dominio propio.

## Consecuencias
- El token de sesión pertenece a un usuario de un Tenant: el Filter de auth debe verificar que el `tenant_id` del usuario coincida con el del header.
- Hosting: backend, frontend de los Tenants y panel SuperAdmin en servicios separados, con un VPS con Docker y Cloudflare adelante.

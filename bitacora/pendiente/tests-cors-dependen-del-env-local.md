# Tests de CORS dependen del `.env` local

`PublicBookingEndpointsTest::testWithoutConfiguredOriginsNoCorsHeadersAreSent` y `CorsConfigTest::testWithoutConfiguredOriginsNoOriginIsAllowed` / `testPatternsAreReadFromTheEnvironment` asumen que `cors.allowedOrigins` no está configurado. Con `cors.allowedOrigins = http://localhost:5173` en el `.env` local fallan.

Son preexistentes (fallan igual sin los cambios de `slot_step_minutes`).

**Pendiente (otro PR):** que los tests aíslen el entorno (limpiar/fijar las variables `cors.*` en `setUp` y restaurarlas después) para no depender del `.env` de cada máquina.

## Mismo patrón: `seed.tenantSlug` (07/10/2026)
`DashboardSeedersTenantTest::testSeederAbortsWithoutWritingWhenTheSlugIsMissing` esperaba que el seeder abortara por falta de `seed.tenantSlug`, pero el `.env` local ya lo define (se agregó para sembrar la base de desarrollo). Su helper `setSeedSlug(null)` solo hacía `unset` de `$_ENV` y `$_SERVER`, y `env()` de CI4 también lee `getenv()`. Corregido agregando `putenv('seed.tenantSlug')`.

Esto sugiere auditar el resto de los tests que leen `env()` o `getenv()` (`tenant.fallbackSlug`, `cors.*`, `app.trustedProxies`...): cualquiera que asuma una variable ausente falla si el `.env` de la máquina la define.

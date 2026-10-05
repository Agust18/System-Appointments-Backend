# Tests de CORS dependen del `.env` local

`PublicBookingEndpointsTest::testWithoutConfiguredOriginsNoCorsHeadersAreSent` y `CorsConfigTest::testWithoutConfiguredOriginsNoOriginIsAllowed` / `testPatternsAreReadFromTheEnvironment` asumen que `cors.allowedOrigins` no está configurado. Con `cors.allowedOrigins = http://localhost:5173` en el `.env` local fallan.

Son preexistentes (fallan igual sin los cambios de `slot_step_minutes`).

**Pendiente (otro PR):** que los tests aíslen el entorno (limpiar/fijar las variables `cors.*` en `setUp` y restaurarlas después) para no depender del `.env` de cada máquina.

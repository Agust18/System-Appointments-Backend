# AppointmentServiceRequiredException era código muerto

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › ✅ Cerrado › Fixes posteriores a R6 (30/09/2026)

## Detalle

- ✅ **`AppointmentServiceRequiredException` era código muerto**: el converter castea `service_id` NULL a 0. Guard ahora `=== null || <= 0`; turno legacy sin servicio devuelve 422 (antes 404 engañoso). El front ya lo maneja genéricamente (`parseApiError` → toast con `data.error`). Commit `648f8ed`.

# DELETE /appointments/{id} devolvía 500

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › ✅ Cerrado › Fixes posteriores a R6 (30/09/2026)

## Detalle

- ✅ **`DELETE /appointments/{id}` devolvía 500**: `AppointmentDeleterService` no inicializaba `$appointmentModel`. Fix de 1 línea + `AppointmentDeleterServiceTest` (borrado, no afecta otros turnos, pasado 403, inexistente 404). Commit `478d32e`.

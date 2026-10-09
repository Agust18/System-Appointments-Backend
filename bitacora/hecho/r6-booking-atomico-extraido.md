# R6 — Booking atómico extraído a AppointmentSlotLockService

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › ✅ Cerrado › Booking / transacciones (30/09/2026)

## Detalle

- ✅ **R6 — Booking atómico extraído**: nuevo Service delegado `AppointmentSlotLockService::run()` (lock `GET_LOCK` + transacción + validación de servicio + bloque + solapamiento + persistencia inyectada). `AppointmentBookingService` y `AppointmentUpdateBookingService` quedan como adaptadores finos. 22 tests de caracterización escritos antes del refactor (`tests/database/Appointment/`), idénticos antes y después. Suite: 242 tests OK. Endpoints verificados a mano: POST 200, PUT 200, solapamiento 409.

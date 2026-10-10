# AppointmentOverlapValidatorService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026 (referencias actualizadas 09/10/2026)
- **Origen:** Refactor de validators de Appointment (se dejó afuera a propósito)

## Detalle

- Extraer el chequeo de solapamiento (`AppointmentModel::hasOverlap` + `AppointmentAlreadyExistsException`) de `AppointmentBookingService::runLocked()` a un validator propio. (Antes vivía en `AppointmentSlotLockService`, fusionado en `AppointmentBookingService` el 09/10/2026.)
- **Severidad:** baja. Toca concurrencia (lock → check → persist) y el beneficio es de ubicación, no de comportamiento.
- El validator tiene que seguir invocándose **dentro** del lock y la transacción.

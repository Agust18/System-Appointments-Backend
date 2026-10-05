# AppointmentOverlapValidatorService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026
- **Origen:** Refactor de validators de Appointment (se dejó afuera a propósito)

## Detalle

- Extraer el chequeo de solapamiento (`AppointmentModel::hasOverlap` + `AppointmentAlreadyExistsException`) de `AppointmentSlotLockService` a un validator propio.
- **Severidad:** baja. Toca concurrencia (lock → check → persist) y el beneficio es de ubicación, no de comportamiento.
- El validator tiene que seguir invocándose **dentro** del lock y la transacción.

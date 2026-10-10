# Micro-deuda: guard de servicio requerido en AppointmentUpdateValidatorService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Deuda técnica backend

## Detalle

- **Micro-deuda**: el guard de `AppointmentUpdateValidatorService` (`$finalServiceId === null || $finalServiceId <= 0` → `AppointmentServiceRequiredException`) tiene un `=== null` inalcanzable por tipos. Se puede simplificar a `<= 0`.
- Antes vivía en `AppointmentUpdateBookingService`, que se fusionó en `AppointmentBookingService` (09/10/2026); la regla ya estaba en el validator.

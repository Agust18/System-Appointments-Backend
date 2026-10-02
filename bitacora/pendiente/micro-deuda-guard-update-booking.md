# Micro-deuda: guard de AppointmentUpdateBookingService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Deuda técnica backend

## Detalle

- **Micro-deuda**: el guard de `AppointmentUpdateBookingService` tiene `=== null || <= 0`, pero `=== null` es inalcanzable por tipos. Se puede simplificar a `<= 0`.

# Refactor de Services de Appointment (menos delegación)

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 09/10/2026
- **Origen:** Análisis de `app/Services/Appointment/` (sobreingeniería y delegación excesiva)

## Detalle

- **Booking en una sola clase:** `AppointmentBookingService` expone `book()` (alta) y `reschedule()` (edición) sobre un `runLocked()` privado (lock + transacción + bloque + solapamiento + persistencia). Se eliminaron `AppointmentSlotLockService` y `AppointmentUpdateBookingService`. Lo usan el panel y la reserva pública.
- **`ServiceBlockResolverService::resolve($medicalId, ?$serviceId)`:** carga servicio y asignación, valida la oferta y calcula el bloque. Reemplaza el trío repetido en `SlotValidator`, `BookingService`, `Availability` y `AvailabilityBatch`. `ServiceDurationCalculatorService` se mantiene (función pura, con test).
- **`AppointmentAvailabilityValidatorService`:** reemplaza a los validators `Query`, `Date` y `Range` (`validateQuery`, `validateDate`, `validateRange`).
- **`AppointmentMedicalActiveValidatorService::validate($medicalId): User`:** regla única de "médico existente con usuario activo", usada por alta (panel y pública), edición y disponibilidad (día y rango).
- **Cambio de comportamiento (único):** la disponibilidad por rango (`from`/`to`) ahora rechaza médicos inactivos (`MedicalNotAvailableException`), igual que la de un día. Antes devolvía slots. Afecta a `/appointments/availability` y `/public/appointments/availability` (mismo controller).
- Se dejaron las notificaciones como estaban (un Service por aviso) a propósito, y `isDayOff()` se conservó como hook en ese momento; después se eliminó por ser especulativo (ver `dias-no-laborables-chequeo-en-booking.md`).
- Tests: `AppointmentUpdateBookingServiceTest` → `AppointmentRescheduleTest`; validaciones unitarias renombradas a `...QueryValidationTest` y `...RangeValidationTest`. 202 tests del módulo, flujo público y permisos en verde; único fallo: [tests de CORS dependen del .env local](../pendiente/tests-cors-dependen-del-env-local.md) (preexistente).
- Doc actualizado: `.claude/rules/booking-atomico.md`.

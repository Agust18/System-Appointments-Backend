# Booking atómico de turnos

`AppointmentBookingService` es la única puerta de escritura de turnos y la usan
el panel (`AppointmentCreatorService`, `AppointmentUpdaterService`) y la reserva
pública (`PublicAppointmentCreatorService`):

- `book(AppointmentRequest)`: alta.
- `reschedule(Appointment, AppointmentUpdateRequest, id)`: edición. Calcula los
  valores finales (request sobre el turno actual) y excluye el propio turno del
  chequeo de solapamiento.

Ambos pasan por el mismo `runLocked()`, que garantiza concurrencia segura:

1. Advisory lock MySQL: `GET_LOCK('appt_lock_{medicalId}_{fecha}', 10)`.
2. `$db->transBegin()`.
3. Recalcula duración efectiva con `ServiceBlockResolverService` (valida servicio
   activo + ofrecido por el médico; servicio base + override del médico + buffer).
4. Verifica solapamiento con `AppointmentModel::hasOverlap()`.
5. Persiste (insert / update) y `transCommit()`. En excepción: `transRollback()`.
6. Libera lock en `finally` con `RELEASE_LOCK`.

`ServiceBlockResolverService` también lo usan la validación del horario y la
disponibilidad. "Médico con usuario activo" es `AppointmentMedicalActiveValidatorService`
(alta, edición, disponibilidad de un día y por rango).

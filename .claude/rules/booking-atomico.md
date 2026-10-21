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
3. Rechaza un **día no laborable** con `MedicalDaysOffService::assertNotDayOff()`
   (`DayOffException`, 409, `code` `DAY_OFF`): bloqueo de la clínica o del médico que cubra la fecha.
   Es la barrera real; `AppointmentSlotValidatorService` hace el mismo chequeo antes del lock para
   dar el mensaje temprano, y la disponibilidad no ofrece el día.
4. Recalcula duración efectiva con `ServiceBlockResolverService` (valida servicio
   activo + ofrecido por el médico; servicio base + override del médico + buffer).
5. Verifica solapamiento con `AppointmentModel::hasOverlap()`.
6. Persiste (insert / update) y `transCommit()`. En excepción: `transRollback()`.
7. Libera lock en `finally` con `RELEASE_LOCK`.

Los bloqueos viven en `medical_days_off` (`DayOffModel`, CRUD en `/days-off`); contrato en
`bitacora/hecho/contrato-dias-no-laborables.md`. Crear un bloqueo no cancela turnos ya agendados, y
`findUpcomingIn24Hours()` sigue mandando el recordatorio de uno que quedó adentro.

`ServiceBlockResolverService` también lo usan la validación del horario y la
disponibilidad. "Médico con usuario activo" es `AppointmentMedicalActiveValidatorService`
(alta, edición, disponibilidad de un día y por rango).

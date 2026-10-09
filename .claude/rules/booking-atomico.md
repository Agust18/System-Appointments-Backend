# Booking atómico de turnos

`AppointmentBookingService::book()` garantiza concurrencia segura:

1. Advisory lock MySQL: `GET_LOCK('appt_lock_{medicalId}_{fecha}', 10)`.
2. `$db->transBegin()`.
3. Recalcula duración efectiva (servicio base + override del médico + buffer).
4. Verifica solapamiento con `AppointmentModel::hasOverlap()`.
5. Inserta y `transCommit()`. En excepción: `transRollback()`.
6. Libera lock en `finally` con `RELEASE_LOCK`.
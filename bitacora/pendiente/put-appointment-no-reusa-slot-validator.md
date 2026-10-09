# Gap funcional: el PUT de turnos no reusa AppointmentSlotValidatorService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026
- **Origen:** Refactor de validators de Appointment (cambia comportamiento → ticket aparte)

## Detalle

- Al editar un turno (fecha/hora/médico/servicio) no se reusa `AppointmentSlotValidatorService`. Un PUT puede mover el turno fuera del horario del médico, a un día no atendido o a un bloque fuera de rango. Hoy solo se valida `fecha < hoy` y solapamiento.
- **Severidad:** alta. Es un bug real, no solo deuda.
- **Decisión pendiente:** si el PUT debe reusar las mismas validaciones que el create.

# Tope de turnos futuros "blando"

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Detalle

- **Tope de turnos futuros "blando".** `PublicAppointmentCreatorService` cuenta los turnos activos del paciente antes de reservar, sin lock: dos solicitudes simultáneas del mismo paciente podrían pasar el tope por uno. Si molesta, resolver con un lock por paciente (como el de `public_identity_{dni}`).

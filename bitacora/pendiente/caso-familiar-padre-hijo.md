# Caso familiar (padre/hijo)

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Detalle

- **Caso familiar (padre/hijo).** Un padre reserva para su hijo desde la reserva pública:
  - Hijo con **DNI propio** → sin problema (caso A).
  - Hijo con el **email del padre** → `409` neutral (caso C: el email ya es de otro usuario).
  - Hijo **sin DNI** → no puede reservar (el DNI es obligatorio).
  - **Solución actual:** la resuelve recepción a mano (alta del paciente con `POST /patients` y turno desde el panel). La solución definitiva son los perfiles de familia (ver "Features futuras").

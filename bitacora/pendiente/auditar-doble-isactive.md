# Auditar el doble isActive (users + medicals)

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Deuda técnica backend

## Detalle

- **Doble `isActive` (users + medicals)**: un médico tiene `isActive` como usuario (puede loguearse) y como médico (puede atender). Revisar si la lógica de negocio es coherente en los 4 cruces posibles:
  - user activo + medical activo → normal
  - user activo + medical inactivo → puede loguearse pero no debería aparecer para asignar turnos
  - user inactivo + medical activo → no puede loguearse pero aparece en listas
  - user inactivo + medical inactivo → dado de baja total
  Confirmar que los listados y validaciones respetan esto.
  - **Ya cubierto en la reserva pública:** `PublicAppointmentCreateValidatorService` exige médico **y** usuario activos, y `GET /public/medicals/available` filtra `M.is_active = 1 AND U.is_active = 1`.
  - **Falta auditar** el resto de los listados autenticados: el filtro `isActive` de `MedicalModel` mira solo `M.is_active` (no el del usuario), así que un médico con usuario inactivo puede aparecer.

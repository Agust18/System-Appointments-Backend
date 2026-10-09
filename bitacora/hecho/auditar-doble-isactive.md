# Auditar el doble isActive (users + medicals)

- **Estado:** ✅ Hecho
- **Repo:** backend

## Hallazgos

1. Los listados autenticados de `MedicalModel` filtraban solo por `M.is_active`.
2. `AppointmentCreateValidatorService` (alta autenticada) no validaba médico/usuario activos.
3. La sincronización user → medical era de una sola vía (`UserSyncService`).

## Resolución

Se eliminó `medicals.is_active`; el estado de un médico es `users.is_active` (vacaciones o baja = desactivar el usuario).

- Migración `2026-10-09-120000_DropIsActiveFromMedicals` (el `down()` reconstruye desde `users.is_active`).
- `isActive` salió de toda la API de médicos (respuestas, filtro y PUT). `user.isActive` en el alta se conserva.
- Listados de reserva (`/public/medicals/available`) filtran `U.is_active = 1`; los listados internos muestran a todos.
- `AppointmentCreateValidatorService` y `AppointmentUpdateValidatorService` (al reasignar médico) lanzan `MedicalNotAvailableException` (409) si el usuario está inactivo.
- `MedicalScheduleCreateValidatorService` y los validadores públicos chequean el usuario.
- `UserSyncService` eliminado.

## Nota

`AppointmentAvailabilityService` (consulta de slots) también responde 409 (`MedicalNotAvailableException`) si el usuario del médico está inactivo.

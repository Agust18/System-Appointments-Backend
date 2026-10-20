# Permisos: recepción edita médicos; el médico cancela y deja nota

- **Estado:** ✅ Hecho
- **Repo:** backend (+ frontend)
- **Fecha:** 15/10/2026
- **Origen:** auditoría de reglas por rol (ADMIN / RECEPTION / MEDICAL / PATIENT)

## Qué cambió

| Regla | Antes | Ahora |
|---|---|---|
| `PUT /medicals/{id}` | `adminOnly` | `adminOrReception` (RECEPTION ya creaba médicos y no podía corregirlos). `DELETE` sigue `adminOnly` |
| MEDICAL en `PUT /appointments/{id}` | solo `status: completed` | `completed` **o** `cancelled`, y puede mandar `nota`. Sigue sin poder tocar médico, paciente, servicio, fecha ni hora |
| `DELETE /patients/{id}` con turnos | `Exception` genérica (500) | `PatientHasAppointmentsException` (409) |

El aviso al paciente por cancelación ya existía (`AppointmentUpdateNotificationService`); no cambió.

## Archivos

- `app/Config/Routes.php`, `AppointmentUpdateValidatorService`, `MedicalCanOnlyCompleteException` / `MedicalCanOnlyChangeStatusException` (mensajes), `PatientHasAppointmentsException`.
- Tests: `AppointmentUpdateValidatorServiceTest` (nota al completar, cancelar propio turno).
- Frontend: `DetailView` rediseñado (compartido por turnos, médicos y pacientes); el médico edita estado (solo completar/cancelar) y nota.

## Relacionado, sigue pendiente

- [PUT de turnos no reusa AppointmentSlotValidatorService](../pendiente/put-appointment-no-reusa-slot-validator.md): toca el mismo validator.

# PUT /appointments/{id} con fecha/hora vacías y `excludeAppointmentId` en disponibilidad

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Fecha:** 16/10/2026
- **Origen:** formulario de edición de turnos del frontend (manda el body completo)

## Contrato

1. `PUT /appointments/{id}`: `fecha` u `hora` presentes pero **vacías** (`''`) responden **422** con error por campo (`errors.fecha` / `errors.hora`: "La fecha no puede estar vacía." / "La hora no puede estar vacía."). Antes daban 500 (`reschedule()` armaba una fecha inválida). El formato de las no vacías sigue validándolo la regla del DTO (`permit_empty|valid_date` / `regex_match`). Clave ausente o `null` = sin cambio (updates parciales y flujos de médico/paciente intactos). `nota: ''` sigue permitida.
2. `GET /appointments/availability` (autenticada, día o rango): parámetro opcional **`excludeAppointmentId`** (entero positivo). Ese turno no cuenta como ocupado, para que al editar un turno su propio horario (y los vecinos que se solapan con su bloque) aparezcan libres. Inválido (`0`, negativo, no numérico, array, vacío) → **422** `errors.excludeAppointmentId = "excludeAppointmentId debe ser un entero positivo"`. Sin el parámetro, comportamiento idéntico. Sigue filtrando por médico y `tenant_id` (un id ajeno o inexistente no excluye nada).
3. `GET /public/appointments/availability` **ignora** el parámetro: tiene su propio controller (`PublicAppointmentAvailabilityGetController`), que ni lo lee ni lo valida.

## Dónde vive cada regla

- Rechazo de `fecha`/`hora` vacías: `AppointmentUpdateValidatorService::validateScheduleFieldsNotBlank()` (corre antes que cualquier otra validación).
- Validación de `excludeAppointmentId`: `AppointmentAvailabilityValidatorService::validateExcludeAppointmentId()`, que recibe el valor crudo; el controller lo pasa sin tocarlo.
- Filtro `AND id != ?`: parámetro opcional de `AppointmentModel::findActiveHoursByMedicalAndFecha()` y `...Range()`; la decisión de excluir es del service.

## Verificación

- Probado a mano en la app: al editar un turno aparecen los horarios vecinos libres.
- Se escribieron tests (unit del validator y de las reglas, y de base de datos para disponibilidad de un día y de rango, más el endpoint público), pero **no se volvieron a correr después del último refactor** (validación movida de controller/DTO al validator, controller público separado).
- Pendiente aparte, sin tocar: `put-appointment-no-reusa-slot-validator.md` (decisión del dueño).

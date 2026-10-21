# Días no laborables: dónde hay que chequearlos (ex R5)

- **Estado:** ✅ Hecho (21/10/2026), con la feature de días no laborables (PR #14)
- **Repo:** backend
- **Esfuerzo:** 30 min
- **Fecha:** 09/10/2026
- **Origen:** PENDIENTES-BACKEND.md › R5 «Extraer isDayOff() a MedicalDaysOffService». Reformulada el 09/10/2026

> Plan completo de la feature (decisiones, tabla, CRUD, frontend): `bitacora/features/dias-no-laborables.md` en la raíz del monorepo (20/10/2026).

## Cómo quedó

`MedicalDaysOffService` (`app/Services/DayOff/`) consulta `medical_days_off` (bloqueos de la clínica o de un médico) y lo usan los cuatro lugares:

| Dónde | Qué hace |
|---|---|
| `AppointmentBookingService::runLocked()` | Rechaza el turno dentro del lock con `DayOffException` (409, `code` `DAY_OFF`). Es la barrera real: cubre alta, reprogramación y reserva pública |
| `AppointmentSlotValidatorService` | Lo mismo antes de tomar el lock, para dar el error temprano con mensaje claro |
| `AppointmentAvailabilityService` | Un día bloqueado devuelve `[]` |
| `AppointmentAvailabilityBatchService` | Un día bloqueado no aparece en el rango |

Verificado con `DayOffTest` (`tests/database/Appointment/`). Contrato: [contrato-dias-no-laborables.md](contrato-dias-no-laborables.md); plan: `bitacora/features/dias-no-laborables.md` en la raíz del monorepo.

## Contexto original

Al 09/10/2026 no existía la feature de días no laborables (feriados, licencias, día bloqueado ad-hoc): no había tabla `medical_days_off` ni Service. El hook `isDayOff()` que devolvía `false` siempre se **eliminó** de `AppointmentAvailabilityBatchService` el 09/10/2026 por ser código especulativo (reemplaza la decisión anterior de conservarlo).

## Qué había que hacer (ya hecho, ver arriba)

Crear `MedicalDaysOffService` (query contra `medical_days_off`) y usarlo en **los tres** lugares, no solo en la disponibilidad:

| Dónde | Para qué |
|---|---|
| `AppointmentAvailabilityBatchService` | Que un día bloqueado no aparezca en el output del rango |
| `AppointmentAvailabilityService` | Lo mismo para la consulta de un solo día |
| `AppointmentBookingService::runLocked()` | **Lo importante:** rechazar el turno dentro del lock. Sin esto, alguien puede reservar directo un día bloqueado aunque el selector no lo muestre |

Conviene validar también en `AppointmentSlotValidatorService`, para que el error llegue antes de tomar el lock y con mensaje claro.

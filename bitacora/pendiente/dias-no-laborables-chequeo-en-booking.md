# Días no laborables: dónde hay que chequearlos (ex R5)

- **Estado:** ⏳ Pendiente (depende de la feature de días no laborables)
- **Repo:** backend
- **Esfuerzo:** 30 min (una vez que exista la feature)
- **Fecha:** 09/10/2026
- **Origen:** PENDIENTES-BACKEND.md › R5 «Extraer isDayOff() a MedicalDaysOffService». Reformulada el 09/10/2026

## Contexto

Hoy no existe la feature de días no laborables (feriados, licencias, día bloqueado ad-hoc): no hay tabla `medical_days_off` ni Service. El hook `isDayOff()` que devolvía `false` siempre se **eliminó** de `AppointmentAvailabilityBatchService` el 09/10/2026 por ser código especulativo (reemplaza la decisión anterior de conservarlo).

## Qué hay que hacer cuando se implemente la feature

Crear `MedicalDaysOffService` (query contra `medical_days_off`) y usarlo en **los tres** lugares, no solo en la disponibilidad:

| Dónde | Para qué |
|---|---|
| `AppointmentAvailabilityBatchService` | Que un día bloqueado no aparezca en el output del rango |
| `AppointmentAvailabilityService` | Lo mismo para la consulta de un solo día |
| `AppointmentBookingService::runLocked()` | **Lo importante:** rechazar el turno dentro del lock. Sin esto, alguien puede reservar directo un día bloqueado aunque el selector no lo muestre |

Conviene validar también en `AppointmentSlotValidatorService`, para que el error llegue antes de tomar el lock y con mensaje claro.

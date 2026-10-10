# R5 — Extraer isDayOff() a MedicalDaysOffService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** 30 min
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Refactors backend (también en 🟢 Consistencia de código). Depende de la feature general dias-no-laborables

## Detalle

| # | Refactor | Dónde | Esfuerzo | Impacto |
|---|---|---|---|---|
| R5 | Extraer `isDayOff()` a `MedicalDaysOffService` | `AppointmentAvailabilityBatchService` | 30 min | Bajo |

> **R5 — Detalle:** extraer `isDayOff()` de `AppointmentAvailabilityBatchService` a `MedicalDaysOffService`, inyectarlo en batch, single-date y Booking, e implementar la query real contra `medical_days_off`. Se hace **cuando se implemente la feature de días no laborables** (hoy `isDayOff()` devuelve `false`).

> **Decisión 09/10/2026 (primera):** `isDayOff()` se conserva tal cual en `AppointmentAvailabilityBatchService` como hook para la feature de días no laborables; no se borra en limpiezas.
>
> **Actualización 09/10/2026 (posterior):** el hook se **eliminó** en una limpieza de sobreingeniería (devolvía `false` siempre y nada lo usaba). Reemplaza a la decisión anterior. Cuando se implemente la feature, el chequeo se agrega en `AppointmentAvailabilityBatchService`, `AppointmentAvailabilityService` y `AppointmentBookingService` junto con `MedicalDaysOffService`; este R5 deja de ser una extracción y pasa a ser parte de esa feature.

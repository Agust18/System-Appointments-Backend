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

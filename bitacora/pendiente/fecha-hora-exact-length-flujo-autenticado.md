# Flujo autenticado: fecha/hora sin largo exacto

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Detalle

- **Flujo autenticado: `fecha`/`hora` sin largo exacto.** `AppointmentRequest` usa `valid_date[Y-m-d]` sin `exact_length`: `2026-10-5` y `2026-10-05` son el mismo día pero generan locks distintos (`appt_lock_{id}_{fecha}`), así que dos reservas simultáneas no se serializan. Además `hora` solo tiene `required` y el validador compara horas como strings (`9:30` vs `10:00` da mal). Pendiente (sin fase asignada): agregar `exact_length[10]` / `exact_length[5]` + `valid_date[H:i]`, como ya hace `PublicAppointmentRequest`. Tocar también `AppointmentUpdateRequest`. Es un cambio de contrato para el front autenticado: verificar que mande siempre `YYYY-MM-DD` y `HH:MM` con ceros.

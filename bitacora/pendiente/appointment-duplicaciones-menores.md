# Duplicaciones menores en los Services de Appointment

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026
- **Origen:** Refactor de validators de Appointment

## Detalle

- **Severidad:** baja.
- `(int) date('N', strtotime($fecha))` repetido en `AppointmentAvailabilityService`, `AppointmentAvailabilityBatchService` (×2) y `AppointmentSlotValidatorService`. Extraer un helper si se vuelve a tocar.
- `DateTime` armado como `"{$fecha} {$hora}:00"` en `AppointmentSlotValidatorService` y `AppointmentSlotLockService`: contextos distintos (validación previa vs. cálculo dentro del lock). **No tocar.**

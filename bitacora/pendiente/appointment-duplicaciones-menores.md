# Duplicaciones menores en los Services de Appointment

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026 (actualizado 09/10/2026)
- **Origen:** Refactor de validators de Appointment

## Detalle

- **Severidad:** baja.
- `(int) date('N', strtotime($fecha))` repetido en `AppointmentAvailabilityService`, `AppointmentAvailabilityBatchService` (×2) y `AppointmentSlotValidatorService`. Extraer un helper si se vuelve a tocar.
- `DateTime` armado como `"{$fecha} {$hora}:00"` en `AppointmentSlotValidatorService` y `AppointmentBookingService::runLocked()` (antes `AppointmentSlotLockService`): contextos distintos (validación previa vs. cálculo dentro del lock). **No tocar.**
- Limpiezas menores sin cambio de comportamiento, detectadas en el análisis del 09/10/2026 y sin aplicar:
  - `SlotGeneratorService`: `if ($candidateEnd > $rangeEndMinutes) continue;` es inalcanzable por la condición del `for`.
  - `AppointmentAvailabilityBatchService`: el `try/catch` alrededor de `SlotGenerator` (función pura) no se alcanza.
  - `AppointmentDeleteValidatorService`: constructor vacío.
  - `AppointmentUpdaterService`: hace `find($id)` dos veces (estado previo y a modificar); alcanza con un `clone`.

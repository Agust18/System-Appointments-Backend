# Gap funcional: el PUT de turnos no reusa AppointmentSlotValidatorService

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 03/10/2026 (revisado 09/10/2026)
- **Origen:** Refactor de validators de Appointment (cambia comportamiento → ticket aparte)

## Detalle

- Al editar un turno (fecha/hora/médico/servicio) no se reusa `AppointmentSlotValidatorService`. Un PUT puede mover el turno fuera del horario del médico, a un día no atendido o a un bloque fuera de rango. Hoy `AppointmentUpdateValidatorService` solo valida `fecha < hoy`, médico activo si cambia y servicio presente.
- **Severidad:** alta. Es un bug real, no solo deuda.
- **Decisión pendiente:** si el PUT debe reusar las mismas validaciones que el create.

## Revisión (09/10/2026)

Qué cubre hoy el PUT y qué no:

| Regla | Create | PUT |
|---|---|---|
| Servicio activo y ofrecido por el médico | sí | **sí** (`AppointmentBookingService::runLocked()` vía `ServiceBlockResolverService`, dentro del lock) |
| Solapamiento | sí | **sí** (idem, excluye el propio turno) |
| Médico con usuario activo | sí | sí, solo si cambia el médico |
| Fecha/hora no pasada | sí (fecha + hora) | solo `fecha < hoy`; **no** la hora de hoy |
| Médico atiende ese día de la semana | sí | **no** |
| Bloque completo dentro de un rango horario | sí | **no** |

Es decir, el gap real son los 3 últimos renglones; la validación de servicio ya quedó cubierta desde el R6.

## Cómo engancharlo (propuesta, sin implementar)

`AppointmentMedicalActiveValidatorService::validate()` devuelve el usuario del médico, así que alcanza con reemplazar el chequeo condicional actual por:

```php
if ($this->changesSchedule($request, $appointment)) {
    $doctor = $this->medicalActiveValidator->validate($finalMedicalId);
    $this->slotValidator->validate($finalMedicalId, $finalServiceId, $finalFecha, $finalHora, $doctor->getFullName());
}
```

Puntos a cuidar:

- **Solo cuando cambia algo del horario** (fecha, hora, médico o servicio distinto del actual). Si no, cancelar/confirmar/completar un turno de hoy con la hora ya pasada, o editar solo la nota de un turno legacy, fallarían con "fecha pasada" o "fuera de horario".
- **Comparar normalizado**: el front reenvía `09:00` y la base guarda `09:00:00` (misma lógica que `AppointmentUpdateNotificationService::wasRescheduled()`); un PUT que reenvía el body completo no debe contar como cambio.
- **Valores finales**: los mismos que arma `AppointmentBookingService::reschedule()` (request sobre el turno actual).
- Se dispara en el validator, antes de tomar el lock (igual que en el create).
- **Tests:** el test unitario de `AppointmentUpdateValidatorServiceTest` hoy no toca la base; el slot validator sí, así que los casos nuevos van en `tests/database/Appointment/`.
- Cambia el comportamiento visible del PUT (nuevos 4xx), por eso queda como decisión.

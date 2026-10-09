# Eliminado `slot_step_minutes` (paso) — cambio de contrato para el frontend

El paso de la grilla es siempre `duración + buffer` del servicio (con override del médico). El campo era redundante y se eliminó (migración `2026-10-05-100000_DropSlotStepMinutesFromServices`).

## Endpoints afectados
- `POST /services` — request
- `PUT /services/{id}` — request
- `GET /services`, `GET /services/{id}`, y las respuestas de `POST`/`PUT /services` — response
- `GET /medicals/{id}/services` (listado con proyección) — response

## Campos que desaparecen
- Requests: `slotStepMinutes` (en `POST` y `PUT /services`). Si el frontend todavía lo manda, **el backend lo ignora** (no falla).
- Responses: `slotStepMinutes` en `ServiceResponse` y en `MedicalServiceProjectionResponse`.

## Sin cambios
`GET /appointments/availability` y `/public/appointments/availability` devuelven el mismo formato `{start_time, end_time, block_end_time}`; los slots ahora avanzan de a `duración + buffer`.

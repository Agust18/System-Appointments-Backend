# Campo code en los 409 de POST /public/appointments

> **HECHO (20/10/2026).** Las cuatro excepciones `409` de la reserva pública implementan `App\Exception\HasErrorCode` y `ApiExceptionHandler` agrega `code` al body (`{ "error": "...", "code": "SLOT_TAKEN" }`). Es aditivo: no cambia ningún status ni mensaje, y las excepciones sin la interfaz responden igual que antes.

- **Repo:** backend (y front: `bookingErrors.js`)
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Códigos

| `code` | Excepción | Qué hace el wizard |
|---|---|---|
| `SLOT_TAKEN` | `AppointmentAlreadyExistsException` | Vuelve al paso del horario |
| `PAST_DATE` | `AppointmentInPastException` | Vuelve al paso del horario |
| `MEDICAL_NOT_AVAILABLE` | `MedicalNotAvailableException` | Vuelve al paso del médico |
| `BOOKING_LIMIT_REACHED` | `PublicBookingLimitExceededException` | Se queda en el paso del paciente |

Los valores son contrato con el front: `ApiExceptionHandlerBodyTest` los fija. `AppointmentInPastException` y `MedicalNotAvailableException` también se lanzan en el flujo autenticado, así que ahí el `code` también sale (inofensivo).

## Cómo se agrega a otra excepción

Implementar `HasErrorCode` y devolver el código desde `errorCode()`. `ValidationException` no lo usa: su body lleva `errors`.

## Front

`bookingErrors.js` prioriza `data.code` y cae al texto del mensaje si el `code` falta o es desconocido. El patrón de identidad (`no pudimos validar tus datos`) sigue ahí pero el backend ya no emite ese `409` (el único error de identidad es el `404` neutral de `/consulta`): queda como código muerto, junto con `BOOKING_ERROR_KIND.IDENTITY` y su panel.

## Sin código

`DoctorNotAvailableException` (`400`), `ServiceNotAvailableException` y `MedicalServiceNotOfferedException` (`404`) no tienen `code`: el wizard no las distingue.

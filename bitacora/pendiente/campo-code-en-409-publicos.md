# Agregar campo code a los 409 de POST /public/appointments

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** Bajo
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Pendiente (reserva pública)

## Detalle

- **Agregar campo `code` a los `409` de `POST /public/appointments`.** Hoy el wizard distingue identidad / horario ocupado / médico no disponible / tope de turnos / fecha pasada **por el texto** del mensaje (`bookingErrors.js` en el front), así que reescribir un mensaje rompe el flujo en silencio. Con un `code` estable en el body de error (`{ "error": "...", "code": "IDENTITY_MISMATCH" }`) el front deja de depender del texto. Bajo esfuerzo: es un campo aditivo en `ApiExceptionHandler`/las excepciones tipadas. Al agregarlo, avisar al front para que priorice `code` sobre el texto.

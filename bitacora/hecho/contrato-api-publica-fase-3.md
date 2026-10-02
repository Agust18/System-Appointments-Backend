# Contrato: API pública de reserva (Fase 3)

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 02/10/2026
- **Origen:** PENDIENTES-FRONTEND.md (del repo backend, contrato de la API pública) › 🔴 API pública de reserva (Fase 3)

## Detalle

## 🔴 API pública de reserva (Fase 3) — para el wizard sin login

Sin header `Authorization`. Todo bajo `/public`. Los errores son `{ "error": "..." }` (los de validación suman `errors: { campo: mensaje }`).

| Método y ruta | Devuelve |
|---|---|
| `GET /public/specialties` | `{ data: [{ id, name, description, createdAt, updatedAt }], total }` |
| `GET /public/medicals/available?specialtyId=` | `{ data: [{ id, medicoNombre, especialidadNombre, isActive }], total }`. Solo médicos activos. |
| `GET /public/medicals/{id}/services` | `[{ serviceId, name, description, durationMinutes, price }]` (duración y precio efectivos del médico). `409` si el médico está inactivo, `404` si no existe. |
| `GET /public/services` | `{ data: [{ id, name, description, durationMinutes, price }], total }`. Solo servicios activos. |
| `GET /public/appointments/availability?medicalId=&serviceId=&fecha=` | Un día: slots `[{ start_time, end_time, block_end_time }]`. Igual que la ruta autenticada. |
| `GET /public/appointments/availability?medicalId=&serviceId=&from=&to=` | **Rango** (máx. 60 días): `{ data: { "YYYY-MM-DD": ["HH:MM", ...] } }`. Son **strings** `"HH:MM"`, no objetos `{start_time,…}`. Los días sin atención no aparecen; si no hay ninguno, `data` llega como `[]` (PHP serializa el array vacío así), no `{}`. Es lo que usa el wizard. |
| `POST /public/appointments` | `201` con `{ id, fecha, hora, medicoNombre, servicioNombre, status }`. |

- Los listados **ignoran `page`, `size`, `search` e `isActive`**: tope fijo de 100 ítems, sin paginar.
- **`POST /public/appointments`** — body: `firstName`, `lastName`, `email`, `phone`, `dni`, `medicalId`, `serviceId`, `fecha` (`YYYY-MM-DD`, con ceros), `hora` (`HH:MM`, con ceros) y `nota` (opcional, máx. 500). `status` y `patientId` se ignoran. El turno nace **`confirmed`**.
  - `422` validación. `409` con el mensaje *"No pudimos validar tus datos. Si ya sos paciente, ingresá con tu teléfono habitual. Si no, contactá al consultorio."* (DNI con otro teléfono, o email de otro usuario: **el mismo texto a propósito**). `409` horario ocupado, médico no disponible o tope de 3 turnos futuros activos. `423` si hay otra solicitud en curso con el mismo DNI (reintentar en unos segundos).
  - La confirmación por email llega al email **guardado** del paciente, no al tipeado.
  - **`429 Too Many Requests`** (sin límite por usuario: es por IP): `{ "error": "..." }` con el header `Retry-After` (segundos). Lecturas: 60 por minuto; `POST /public/appointments`: 5 por minuto. El wizard no debería disparar ráfagas de GET: cachear especialidades, médicos y servicios durante la sesión, y pedir la disponibilidad de un rango (`from`/`to`) en vez de día por día. Al recibir un 429, mostrar un mensaje y deshabilitar el botón hasta que pase `Retry-After`.
  - **Un DNI bloqueado se ve igual que un teléfono incorrecto** (el mismo `409` neutral): tras 5 teléfonos incorrectos para un DNI, ese DNI queda 1 hora sin poder reservar sin cuenta. El mensaje ya sugiere iniciar sesión o contactar al consultorio; conviene ofrecer esos dos caminos en la pantalla de error.
- **CORS:** solo está habilitado en `/public/*` y para los orígenes de `cors.allowedOrigins` / `cors.allowedOriginsPatterns` en el `.env` del backend (sin ellos no se envía ningún header). Cada cliente nuevo con dominio propio hay que agregarlo ahí. Las rutas autenticadas no tienen CORS: siguen necesitando el mismo origen (p. ej. proxy de Vite).

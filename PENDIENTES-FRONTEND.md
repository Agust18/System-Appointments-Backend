# PENDIENTES — FRONTEND

Última actualización: 02/10/2026

Cambios de contrato del backend que el frontend tiene que tolerar o aprovechar.

---

## 🔴 Cambios de contrato (reserva pública, Fase 2)

1. **`POST /patients/register` puede devolver `202` con `{ "message": "..." }`** en vez de `201` con el paciente.
   - Pasa cuando el DNI ya pertenece a un usuario **sin cuenta** (creado por una reserva pública o por recepción) y el email coincide con el guardado. No se crea nada y se le envía un link a su email para que elija su contraseña.
   - El frontend debe tratar el `202` como éxito y mostrar el `message` ("Te enviamos un email para que actives tu cuenta").
   - La contraseña enviada en el body se ignora en ese caso: la fija el usuario desde el link.
2. **`UserResponse` suma `hasAccount: boolean`.** Es aditivo. `false` = usuario sin contraseña (no puede iniciar sesión hasta activar su cuenta). Sirve para mostrar un badge "sin cuenta" a recepción/admin.

---

## 🔴 API pública de reserva (Fase 3) — para el wizard sin login

Sin header `Authorization`. Todo bajo `/public`. Los errores son `{ "error": "..." }` (los de validación suman `errors: { campo: mensaje }`).

| Método y ruta | Devuelve |
|---|---|
| `GET /public/specialties` | `{ data: [{ id, name, description, createdAt, updatedAt }], total }` |
| `GET /public/medicals/available?specialtyId=` | `{ data: [{ id, medicoNombre, especialidadNombre, isActive }], total }`. Solo médicos activos. |
| `GET /public/medicals/{id}/services` | `[{ serviceId, name, description, durationMinutes, price }]` (duración y precio efectivos del médico). `409` si el médico está inactivo, `404` si no existe. |
| `GET /public/services` | `{ data: [{ id, name, description, durationMinutes, price }], total }`. Solo servicios activos. |
| `GET /public/appointments/availability?medicalId=&serviceId=&fecha=` (o `from`/`to`) | Slots `[{ start_time, end_time, block_end_time }]`. Igual que la ruta autenticada. |
| `POST /public/appointments` | `201` con `{ id, fecha, hora, medicoNombre, servicioNombre, status }`. |

- Los listados **ignoran `page`, `size`, `search` e `isActive`**: tope fijo de 100 ítems, sin paginar.
- **`POST /public/appointments`** — body: `firstName`, `lastName`, `email`, `phone`, `dni`, `medicalId`, `serviceId`, `fecha` (`YYYY-MM-DD`, con ceros), `hora` (`HH:MM`, con ceros) y `nota` (opcional, máx. 500). `status` y `patientId` se ignoran. El turno nace **`confirmed`**.
  - `422` validación. `409` con el mensaje *"No pudimos validar tus datos. Si ya sos paciente, ingresá con tu teléfono habitual. Si no, contactá al consultorio."* (DNI con otro teléfono, o email de otro usuario: **el mismo texto a propósito**). `409` horario ocupado, médico no disponible o tope de 3 turnos futuros activos. `423` si hay otra solicitud en curso con el mismo DNI (reintentar en unos segundos).
  - La confirmación por email llega al email **guardado** del paciente, no al tipeado.
  - **`429 Too Many Requests`** (sin límite por usuario: es por IP): `{ "error": "..." }` con el header `Retry-After` (segundos). Lecturas: 60 por minuto; `POST /public/appointments`: 5 por minuto. El wizard no debería disparar ráfagas de GET: cachear especialidades, médicos y servicios durante la sesión, y pedir la disponibilidad de un rango (`from`/`to`) en vez de día por día. Al recibir un 429, mostrar un mensaje y deshabilitar el botón hasta que pase `Retry-After`.
  - **Un DNI bloqueado se ve igual que un teléfono incorrecto** (el mismo `409` neutral): tras 5 teléfonos incorrectos para un DNI, ese DNI queda 1 hora sin poder reservar sin cuenta. El mensaje ya sugiere iniciar sesión o contactar al consultorio; conviene ofrecer esos dos caminos en la pantalla de error.
- **CORS:** solo está habilitado en `/public/*` y para los orígenes de `cors.allowedOrigins` / `cors.allowedOriginsPatterns` en el `.env` del backend (sin ellos no se envía ningún header). Cada cliente nuevo con dominio propio hay que agregarlo ahí. Las rutas autenticadas no tienen CORS: siguen necesitando el mismo origen (p. ej. proxy de Vite).

---

## 🟡 También cambió (Fases 1-2)

- **DNI y teléfono se normalizan en el backend** (se guardan solo con dígitos). El frontend puede mandarlos con puntos, guiones o espacios y debe **formatearlos solo para mostrar**.
  - DNI válido: 7 a 8 dígitos (antes el registro público exigía exactamente 8).
  - Teléfono válido: solo dígitos, 10 a 15 (ej. `2325506499`). Se quitan `+54`, `549` y el `0` de área. El "15" móvil no se resuelve: mostrar un hint tipo "código de área, sin 0 ni 15".
- **El email se guarda en minúsculas y sin espacios.**
- **`POST /patients` (recepción/admin)**: `password` y `patient.birthDate` pasan a ser opcionales. Sin contraseña el paciente nace sin cuenta.
- **`PatientResponse.birthDate` y `PatientProjectionResponse.birthDate` pueden ser `null`** (la reserva pública no pide fecha de nacimiento).
- **`PUT /patients/{id}`** acepta además `firstName`, `lastName`, `phone` y `email` (todos opcionales). Permite a recepción completar o corregir el teléfono de un paciente.
- **Un paciente existente sin teléfono no podrá usar la reserva pública** hasta que recepción cargue su teléfono. Conviene mostrar `phone` vacío como dato faltante en la ficha del paciente.

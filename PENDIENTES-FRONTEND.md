# PENDIENTES — FRONTEND

Última actualización: 02/10/2026 (Fase 6, Parte A)

Cambios de contrato del backend que el frontend tiene que tolerar o aprovechar.

Fuente de verdad del flujo público en el backend: `.claude/rules/public-booking.md`.

---

## 🔴 Cambios de contrato (reserva pública, Fase 2)

1. **`POST /patients/register` puede devolver `202` con `{ "message": "..." }`** en vez de `201` con el paciente.
   - Pasa cuando el DNI ya pertenece a un usuario **sin cuenta** (creado por una reserva pública o por recepción) y el email coincide con el guardado. No se crea nada y se le envía un link a su email para que elija su contraseña.
   - El frontend debe tratar el `202` como éxito y mostrar el `message` ("Te enviamos un email para que actives tu cuenta"). **No** redirigir al login ni asumir que el body trae un paciente: no hay `id`.
   - Distinguir por status, no por la forma del body:
     ```ts
     const res = await api.post('/patients/register', body);
     if (res.status === 202) return showInfo(res.data.message); // sin cuenta: link por email
     return onRegistered(res.data);                              // 201: paciente creado
     ```
   - La contraseña enviada en el body se ignora en ese caso: la fija el usuario desde el link.
2. **`UserResponse` suma `hasAccount: boolean`.** Es aditivo. `false` = usuario sin contraseña (no puede iniciar sesión hasta activar su cuenta). Sirve para mostrar un badge "sin cuenta" a recepción/admin.

---

## 🔴 Contrato del wizard público (sin login)

Pantallas y endpoints, en orden. Todo sin `Authorization`.

| Paso | Pantalla | Endpoint |
|---|---|---|
| 1 | Especialidad | `GET /public/specialties` |
| 2 | Médico | `GET /public/medicals/available?specialtyId=` |
| 3 | Servicio | `GET /public/medicals/{id}/services` (duración y precio efectivos del médico) |
| 4 | Fecha y horario | `GET /public/appointments/availability?medicalId=&serviceId=&from=&to=` |
| 5 | Datos del paciente | (sin request) `firstName`, `lastName`, `email`, `phone`, `dni`, `nota` opcional |
| 6 | Confirmación | `POST /public/appointments` → `201` `{ id, fecha, hora, medicoNombre, servicioNombre, status }` |

- El turno nace **`confirmed`**: la pantalla final dice "Tu turno fue creado y confirmado", no "pendiente".
- Se envía un email al paciente (al email **guardado**, que puede no ser el tipeado) y otro a recepción.
- **Presupuesto de requests** (límite por IP: 60 GET/min, 5 POST/min): cachear en memoria especialidades, médicos y servicios durante la sesión; pedir la disponibilidad de un **rango** (`from`/`to`) en vez de día por día; no re-pedir al volver atrás un paso.
- **Un solo POST por intento**: deshabilitar el botón mientras la request está en curso (el límite es de 5 por minuto, y un doble click gasta cupo y puede chocar con el `423`).
- Si al llegar al paso 6 el horario fue tomado por otra persona, el `409` vuelve al paso 4 con la disponibilidad refrescada.

### Manejo de errores de `POST /public/appointments`

El body de error es siempre `{ "error": "..." }`; el `422` suma `errors: { campo: mensaje }`.

| Status | Significado | Qué hacer |
|---|---|---|
| `422` | Validación | Marcar cada campo con `errors[campo]`. No limpiar el formulario. |
| `409` identidad | *"No pudimos validar tus datos. Si ya sos paciente, ingresá con tu teléfono habitual. Si no, contactá al consultorio."* | Ver abajo. |
| `409` horario | "El horario seleccionado ya no se encuentra disponible…" | Volver al paso 4 y refrescar los slots. |
| `409` médico | "El profesional seleccionado no está disponible…" | Volver al paso 2. |
| `409` tope | "Ya tenés 3 turnos pendientes o confirmados…" | Mostrar el mensaje; no reintentar. |
| `423` | Otra solicitud en curso (mismo DNI o mismo profesional) | Reintentar una vez automáticamente a los 2-3 s; si vuelve a fallar, mostrar el mensaje. |
| `429` | Límite por IP | Ver abajo. |

**`409` de identidad — distinguir por el texto, no hay código de error.** Es el mismo mensaje para: DNI con otro teléfono, DNI sin teléfono guardado, email de otro usuario, DNI de otro rol o inactivo, y **DNI bloqueado** (5 teléfonos incorrectos = 1 hora sin poder reservar sin cuenta). Es neutral a propósito para no revelar si un DNI existe. En la pantalla de error ofrecer **dos caminos**: "Iniciar sesión" y "Contactar al consultorio" (teléfono/WhatsApp del cliente). No sugerir "probá con otro teléfono": alentaría a adivinar y consume el bloqueo.

**`429` con `Retry-After`:**
- El header trae los segundos de espera: `response.headers['retry-after']`.
- ⚠️ **Hoy el navegador NO puede leerlo si el SPA y el API están en dominios distintos**: `Cors.php` tiene `exposedHeaders` vacío, y sin `Access-Control-Expose-Headers: Retry-After` los headers no seguros quedan ocultos (el `429` sí se lee, porque `cors` ya agregó sus headers). Mismo dominio o proxy (p. ej. Vite en desarrollo): se lee sin problema. Pendiente de backend: agregar `Retry-After` a `exposedHeaders`.
- **Implementar siempre con fallback:** `const wait = Number(headers['retry-after']) || 15;` (segundos).
- Mostrar "Demasiados intentos, probá de nuevo en N segundos" y **deshabilitar el botón** hasta que pase el plazo (cuenta regresiva). No reintentar solo.

**Caso familiar:** un padre que reserva para su hijo con el **email del padre** recibe el `409` de identidad (el email ya pertenece a otro usuario). Hijo sin DNI no puede reservar. Hoy lo resuelve recepción; agregar en el paso 5 un hint "Cada paciente necesita su propio DNI y email. Si reservás para un menor, contactá al consultorio".

---

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

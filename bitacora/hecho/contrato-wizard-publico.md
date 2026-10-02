# Contrato: wizard público (sin login) y manejo de errores

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 02/10/2026
- **Origen:** PENDIENTES-FRONTEND.md (del repo backend, contrato de la API pública) › 🔴 Contrato del wizard público (sin login)

## Detalle

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

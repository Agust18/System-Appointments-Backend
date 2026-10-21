# Contrato: evoluciones de consulta

Lo que el médico registra de una atención (diagnóstico, observaciones, tratamiento, indicaciones). Plan y decisiones: `bitacora/features/evoluciones-de-consulta.md` (raíz del monorepo).

- Cada evolución nace de **un turno** y la escribe **el médico dueño de ese turno**.
- Nace como **borrador** (`status: "draft"`), editable por su autor. Al **firmarla** (`status: "signed"`) se cierra y el **turno pasa a `completed`**.
- Lo firmado no se edita: se corrige creando una **evolución nueva** con `correctsId`. **No hay `DELETE`.**
- Son datos de salud: el **paciente no los lee** (no tiene cuenta) y **cada lectura queda registrada** (`evolution_access_log`).

## Quién accede

| Rol | Lee | Escribe |
|---|---|---|
| Médico | **Solo las que él escribió** | Sí, sobre sus turnos |
| Admin | Todas las del Tenant | No |
| Recepción | **No** (`403` en todo) | No |

## Endpoints (todos con `auth` y `X-Tenant`; `patientId` es el id de la ficha del paciente)

| Método y ruta | Roles | Respuesta |
|---|---|---|
| `GET /patients/{patientId}/evolutions` | admin, médico | `{data: Evolution[], total}` |
| `GET /patients/{patientId}/evolutions/{id}` | admin, médico | `Evolution` |
| `POST /patients/{patientId}/evolutions` | médico | `201` `Evolution` (borrador) |
| `PUT /patients/{patientId}/evolutions/{id}` | médico (autor) | `200` `Evolution` (solo borradores) |
| `POST /patients/{patientId}/evolutions/{id}/sign` | médico (autor) | `200` `Evolution` firmada |

`GET` de lista: `page` y `size` (por defecto 1 y 10), de la más reciente a la más antigua. Una evolución que no es del paciente de la ruta, o que es de otro médico, responde `404` (igual que si no existiera).

### `Evolution`
```json
{ "id": 7, "patientId": 12, "medicalId": 5, "appointmentId": 41,
  "diagnosis": "Faringitis", "observations": null, "treatment": "Reposo", "indications": null,
  "correctsId": null, "status": "draft", "signedAt": null,
  "createdAt": "2026-10-21 10:00:00", "updatedAt": "2026-10-21 10:00:00" }
```
`status` es `draft` o `signed`; `signedAt` es `null` en borrador.

## `POST /patients/{patientId}/evolutions`
```json
{ "appointmentId": 41, "diagnosis": "Faringitis", "observations": "", "treatment": "Reposo", "indications": "",
  "correctsId": null }
```
- `appointmentId` obligatorio. Los textos son opcionales (máx. 5000; `""` se guarda como `null`).
- `correctsId` solo para corregir: id de la evolución **vigente y firmada** del mismo turno.
- El médico sale de la sesión; un `medicalId` en el body se ignora.

## `PUT /patients/{patientId}/evolutions/{id}`
Campo **ausente = sin cambio**. Se envían solo los textos (`diagnosis`, `observations`, `treatment`, `indications`); un `""` limpia el campo.

## `POST .../{id}/sign`
Sin body. Cierra la evolución y pasa el turno a `completed` en la misma operación. Es el único camino por el que un turno queda `completed`.

## Errores
| Código | Cuándo |
|---|---|
| `422` | Validación de campos (`errors` por campo); corrección inválida: la evolución a corregir es de otro turno, es un borrador, o ya fue corregida (mensaje claro, sin `errors`) |
| `404` | Paciente, turno (o turno de otro paciente) o evolución inexistente en el Tenant; evolución de otro paciente o de otro médico |
| `403` | Recepción en cualquier ruta; admin en POST/PUT/sign; médico que no es dueño del turno o autor de la evolución |
| `409` | El turno ya tiene una evolución vigente; el turno no está `confirmed`/`completed`; editar o firmar una evolución ya firmada |

## Notas para el frontend
- Mostrar el botón "Registrar evolución" solo al médico dueño del turno, con el turno `confirmed` o `completed`; si el turno ya tiene evolución, ofrecer verla o corregirla.
- Ocultar las evoluciones a recepción (`useUserRole()`); el `403` del backend manda igual.
- Para corregir: `POST` con `correctsId` = id de la evolución firmada vigente; la nueva nace como borrador y se firma aparte.
- Al firmar, refrescar el turno (pasó a `completed`).

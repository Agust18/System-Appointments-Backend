# Contrato: días no laborables (feriados, vacaciones, licencias)

Bloqueos de **día completo** por rango de fechas. Plan y decisiones: `bitacora/features/dias-no-laborables.md` (raíz del monorepo).

- `medicalId = null`: bloqueo **de la clínica** (vale para todos los médicos). Con valor: solo ese médico.
- `dateFrom` / `dateTo`: `YYYY-MM-DD`, ambos inclusivos. Un solo día = mismo valor.
- Crear un bloqueo **no cancela ni mueve turnos**: la respuesta lista los afectados para que recepción los gestione.

## Endpoints (todos con `auth` y `X-Tenant`)

| Método y ruta | Roles | Respuesta |
|---|---|---|
| `GET /days-off` | todos | `{data: DayOff[], total}` |
| `GET /days-off/{id}` | todos | `DayOff` (`404` si no existe) |
| `POST /days-off` | admin, recepción | `201` `DayOffSaved` |
| `PUT /days-off/{id}` | admin, recepción | `200` `DayOffSaved` |
| `DELETE /days-off/{id}` | admin, recepción | `{id}`. Borrar no avisa nada |

El médico recibe `403` en POST, PUT y DELETE.

### `GET /days-off`
Filtros: `medicalId`, `from`, `to` (`YYYY-MM-DD`), `page`, `size`.
- `medicalId` trae los bloqueos **de ese médico más los de la clínica** (los que afectan su agenda).
- `from` / `to` traen los que **se solapan** con el rango (cualquiera de los dos puede faltar).
- Un médico siempre recibe lo suyo más lo de la clínica, mande lo que mande en `medicalId`.
- Orden: `dateFrom` ascendente.

### `DayOff`
```json
{ "id": 1, "medicalId": null, "dateFrom": "2026-10-12", "dateTo": "2026-10-12",
  "reason": "Feriado", "createdBy": 3, "createdAt": "2026-10-20 10:00:00" }
```

### `DayOffSaved` (POST y PUT)
Los mismos campos más:
```json
"affectedAppointments": [
  { "id": 41, "fecha": "2026-10-12", "hora": "09:00", "pacienteNombre": "Ana Gómez",
    "medicalId": 5, "medicoNombre": "Juan Pérez" }
]
```
Son los turnos `pending` / `confirmed` dentro del rango (del médico, o de todos si es de la clínica). Los `cancelled` / `completed` no aparecen. En el PUT se calculan con las fechas **ya modificadas**.

## `POST /days-off`
```json
{ "medicalId": 5, "dateFrom": "2026-10-12", "dateTo": "2026-10-14", "reason": "Congreso" }
```
`medicalId` ausente o `null` = toda la clínica. `reason` opcional (máx. 255; `""` se guarda como `null`).

## `PUT /days-off/{id}`
Campo **ausente = sin cambio**. Dos campos admiten `null` como valor:
- `{"medicalId": null}`: pasa el bloqueo a "toda la clínica".
- `{"reason": null}`: borra el motivo.

## Errores
| Código | Cuándo |
|---|---|
| `422` | `dateFrom` / `dateTo` con formato inválido; `dateTo` anterior a `dateFrom` (error en `dateTo`); superposición con otro bloqueo **del mismo alcance** (mensaje claro, sin `errors`) |
| `404` | Médico inexistente en el Tenant (`medicalId`), o bloqueo inexistente |
| `403` | Rol médico en POST / PUT / DELETE |

Dos bloqueos solo chocan si tienen el mismo alcance: uno de la clínica y uno de un médico **pueden** superponerse.

## Efecto sobre turnos
Un día bloqueado no admite turnos, ni en el panel ni en la reserva pública, ni al reprogramar.

- **Reservar** (`POST /appointments`, `PUT /appointments/{id}`, `POST /public/appointments`): `409` con `{error, code: "DAY_OFF"}`. El front no debería llegar ahí (la disponibilidad ya no ofrece el día); manejarlo por si hay datos viejos.
- **Disponibilidad** (`GET /appointments/availability`, `GET /public/appointments/availability`, y la versión por rango): el día bloqueado devuelve `[]` y en el rango **no aparece** la fecha. Es lo mismo que un día sin horario cargado.
- **Recordatorios de 24 h**: un turno que quedó dentro de un bloqueo y no se movió **igual recibe el recordatorio**. Recepción tiene que reprogramarlo o cancelarlo a tiempo.

## Fuera de la v1
Bloqueos por rango de horas, feriados que se repiten, importar un calendario oficial, que el médico cargue los suyos, y cancelar turnos en masa con aviso a los pacientes.

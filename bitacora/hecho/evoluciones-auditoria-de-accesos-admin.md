# Evoluciones: ver el registro de accesos (solo admin)

- **Estado:** ✅ Hecho
- **Repo:** backend + frontend
- **Esfuerzo:** mediano
- **Fecha:** 2026-10-21
- **Origen:** revisión de evoluciones

## Qué se hizo

`evolution_access_log` ya guardaba cada lectura; ahora el ADMIN puede consultarla.

- Solo registra **quién** accedió, a qué paciente, qué acción y cuándo. **No se guarda la IP** (decisión del dueño); la tabla no cambió.
- Solo ADMIN: la ruta lleva `role:admin` y el Service también rechaza a otros roles con `403`.

## Contrato

`GET /evolution-access-log` (`auth` + `role:admin`).

| Query | Descripción |
|---|---|
| `page`, `size` | Paginación |
| `search` | Nombre del usuario, nombre del paciente o DNI (máx. 100) |
| `from`, `to` | `YYYY-MM-DD`, inclusive; cualquiera puede faltar |

Respuesta `{data, total}`, del más reciente al más antiguo. Cada fila:

```json
{ "id": 1, "userId": 7, "userName": "Ana Pérez", "patientId": 3, "patientName": "Ana Gómez",
  "evolutionId": null, "action": "list", "createdAt": "2026-10-21 14:30:00" }
```

`action`: `list` (listado de un paciente, `evolutionId` NULL) o `view` (lectura de una evolución). Consultar el registro no deja rastro propio.

## Frontend

Pantalla `/registro-accesos`, pestaña "Registro de accesos" en la sección Accesos (solo admin). Tabla con búsqueda, filtro por fechas y paginación.

## Tests

`tests/database/Evolution/EvolutionTest.php`: nombres y búsqueda, solo admin (médico y recepción `403`) y aislamiento por Tenant. `PlatformRoutesTest`: la matriz de acceso ahora incluye las rutas de evoluciones y esta.

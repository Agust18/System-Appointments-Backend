# `countProjection` y `statsByStatus` de turnos no hacen JOIN a `services`

- **Estado:** pendiente (no es una fuga entre Tenants; inconsistencia previa al multitenant)
- **Repo:** backend
- **Esfuerzo:** bajo
- **Fecha:** 06/10/2026
- **Origen:** test `AppointmentTenantIsolationTest` del Grupo 3 del multitenant

## Detalle
En `AppointmentModel`, `searchProjection` hace `INNER JOIN services SRV`, pero `countProjection` y `statsByStatus` no. Resultado: un turno que no tenga un servicio válido (`service_id` NULL, que la columna admite, o con una referencia de servicio que no cumpla el JOIN) se cuenta en el total y en las estadísticas por estado pero no aparece en la lista. El `total` de la paginación puede no coincidir con las filas que se listan.

Con multitenant el caso es el mismo: un turno del Tenant A cuyo `service_id` apunte a un servicio del Tenant B no aparece en la lista (el JOIN lleva `AND SRV.tenant_id = A.tenant_id`) pero sí en el conteo. La fila es del propio A, por lo que no se filtra ningún dato del B.

## Qué hay que hacer
Decidir cuál es el comportamiento correcto y alinear las tres consultas:
- Agregar el `INNER JOIN services SRV` a `countProjection` y `statsByStatus` (el total y las estadísticas pasan a coincidir con la lista), o
- pasar a `LEFT JOIN services` en las tres (turnos sin servicio también se listan).

Agregar un test que exija `countProjection == count(searchProjection)` y que las estadísticas sumen lo mismo que la lista, incluyendo un turno sin servicio.

# Auditar el modelo de borrado: borrado lógico vs físico

- **Estado:** pendiente (post-multitenant, para otra sesión; no se toca ahora)
- **Repo:** backend
- **Esfuerzo:** a estimar en la auditoría
- **Fecha:** 06/10/2026
- **Origen:** charla aparte durante el multitenant

## Problema
Hoy los borrados son físicos (`DELETE FROM ...`). Evaluar pasar a borrado lógico (`deleted_at`) en las entidades de negocio, y mantener el borrado físico en los datos efímeros.

## Alcance a evaluar

| Borrado lógico (`deleted_at`) | Borrado físico |
|---|---|
| `appointments` | tokens (`user_verification_tokens`) |
| `patients` | logs |
| `medicals` | sesiones |
| `services` | |
| `specialties` | |
| `users` | |
| `tenants` | |

## Qué tiene que cubrir la auditoría
- Inventario de todos los `DELETE` actuales (Models, Services, seeders) y de qué FK tienen `ON DELETE CASCADE`: hoy borrar un `users` arrastra en cascada `medicals`, `patients` y, a través de ellos, `medical_service` y `appointments`. Con borrado lógico esa cascada deja de ocurrir sola y hay que decidir qué pasa con lo dependiente.
- Impacto en los **únicos por Tenant** (`UNIQUE(tenant_id, email)`, `UNIQUE(tenant_id, dni)`, `UNIQUE(tenant_id, name)` de `specialties`): una fila con `deleted_at` seguiría ocupando el email/DNI/nombre. Decidir si el único incluye `deleted_at` o si se libera el valor al borrar.
- Impacto en cada consulta: todo SELECT/UPDATE de esas tablas necesitaría `AND deleted_at IS NULL`, igual que hoy lleva `AND tenant_id = ?`. Evaluar cómo evitar olvidos (misma estrategia de test que el filtro de Tenant).
- Interacción con lo que ya existe: `services` ya tiene un borrado "lógico" propio (`delete()` hace `is_active = 0` y existe `hardDelete()`), y `users` usa `is_active` (`medicals` ya no tiene: el estado del médico es el de su usuario). Decidir si `deleted_at` reemplaza o convive con `is_active`.
- Métricas e historial: qué pasa con los turnos de un paciente o médico borrado en los reportes y en `MetricsModel`.
- `tenants`: qué significa borrar un Tenant (hoy existe `suspended`, que conserva los datos); relación con retención de datos y privacidad (datos de salud, Ley 25.326).
- Los seeders de limpieza del dashboard (`DashboardDemoCleanupSeeder`) dependen del borrado físico en cascada.
- Retención y purga: si hay borrado lógico, definir cuándo y quién purga físicamente.

## Fuera de alcance de este pendiente
Tokens, logs y sesiones siguen con borrado físico (datos efímeros).

Relacionado: `multitenant-plan.md`, `adr/003-usuarios-por-tenant-y-api-unica.md`.

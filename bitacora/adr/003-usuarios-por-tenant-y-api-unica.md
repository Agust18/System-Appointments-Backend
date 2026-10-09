# ADR 003: Usuarios separados por Tenant, SuperAdmin aparte y una sola API

Estado: aceptado (2026-10-05)

## Decisión
1. **Usuarios por Tenant.** `UNIQUE(tenant_id, dni)` y `UNIQUE(tenant_id, email)` reemplazan los únicos globales. La misma persona en dos Tenants son dos usuarios; un Tenant nunca ve que un DNI existe en otra.
2. **SuperAdmin en tabla propia** `platform_admins`, con login propio. `users.tenant_id` es NOT NULL.
3. **Una sola API.** El panel SuperAdmin es un frontend aparte que usa rutas `/platform/*` del mismo backend, protegidas por un filtro `platformAdminOnly`.

## Por qué
- Privacidad y simplicidad de datos.
- Un `tenant_id` nulo en `users` es un riesgo: una consulta olvidada podría tratar al SuperAdmin como usuario de cualquier Tenant.
- Un segundo backend duplicaría Models y migraciones sobre las mismas tablas.

## Consecuencias
- **Limitación documentada: la migración `AddTenantIdToUsersAndPatients` no se puede revertir si hay emails o DNI repetidos entre Tenants** (los únicos globales no podrían volver a crearse). El `down()` lo verifica antes de escribir nada y falla con "No se puede revertir: existen emails/DNI duplicados entre tenants" y la lista de duplicados; la base queda intacta. Para revertir hay que unificar o eliminar esos usuarios a mano. Es una consecuencia directa de esta decisión: apenas dos Tenants compartan un DNI, el rollback deja de ser posible.
- Hay que revisar la migración del 2026-10-02 (únicos de `users.dni` y `patients.user_id`).
- Los módulos se guardan en `tenant_modules` (los nombres viven en código, como constantes); la configuración en `tenant_settings`. SMTP único de plataforma, sin credenciales por Tenant (reversible, no merece ADR).

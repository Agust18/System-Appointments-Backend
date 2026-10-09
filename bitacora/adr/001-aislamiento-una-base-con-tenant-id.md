# ADR 001: Una sola base de datos con `tenant_id`

Estado: aceptado (2026-10-05)

## Decisión
Todos los Tenants comparten una base. Las tablas con datos de un Tenant llevan `tenant_id`. El filtro se centraliza en un `TenantContext`; ningún Service ni Model arma el `WHERE tenant_id` por su cuenta.

## Por qué
- Sin devops, una base por Tenant obliga a automatizar migraciones, backups y conexiones por cliente.
- Escala esperada: menos de ~100 Tenants.
- Los datos pueden ser sensibles, p. ej. datos de salud (Ley 25.326): el riesgo principal es un `WHERE tenant_id` olvidado, por eso se centraliza.

## Alternativas descartadas
Base por Tenant (más aislamiento, mucho más operación). Schema por Tenant (mismo costo operativo en MySQL).

## Consecuencias
- Las claves que se arman con datos que se repiten entre Tenants (el DNI) deben llevar prefijo de Tenant: `public_identity_failures_{tenantId}_...` y el lock `public_identity_{tenantId}_{dni}`. `appt_lock_{medicalId}_{fecha}` no lo necesita: el `medicalId` es único en toda la base.
- Migrar a una base por Tenant más adelante es costoso: de ahí este ADR.

# Multitenant: plan de implementación

- **Estado:** pendiente (nada implementado)
- **Repo:** backend (más el frontend de los Tenants y un panel SuperAdmin nuevo)
- **Esfuerzo:** alto, por pasos
- **Fecha:** 05/10/2026
- **Origen:** sesión de análisis de escalabilidad

Decisiones firmes: ver `bitacora/glosario.md` y `bitacora/adr/001`, `002`, `003`. Este archivo es solo el plan.

## Decisiones resumidas

- Tenant = organización cliente de la plataforma (término genérico, no atado al rubro), sin sedes. Una sola base con `tenant_id` (ADR 001).
- Subdominio `{slug}.tusturnos.com`; la SPA manda `X-Tenant: {slug}` a la API (ADR 002).
- Usuarios separados por Tenant; SuperAdmin en `platform_admins`; una sola API con `/platform/*` (ADR 003).
- Un solo SMTP de plataforma; cada Tenant configura nombre y `reply_to_email`.
- Núcleo siempre activo; módulos con toggle: `public_booking`, `metrics`, `email_notifications`.
- Tenant suspendido: 403 en todas sus rutas, datos conservados.
- `TenantContext` estático que falla si no se completó. Cada Model agrega `AND tenant_id = ?` explícito.
- Los datos actuales son ficticios: no hay migración de datos reales.
- Hosting: backend, frontend de los Tenants y panel en servicios separados; VPS con Docker, Cloudflare adelante.
- En local: `{slug}.localhost`.

## Pasos

### Paso 1: Tenant resuelto en cada request — HECHO (06/10/2026)
Implementado: migración `tenants`, `TenantStatus`, `Tenant`, `PrimitiveToTenantConverter`, `TenantModel`, excepciones, `TenantContext`, `TenantResolverService` y el filtro `tenant` (alias registrado en `Config/Filters.php`). Tests: `TenantContextTest` y `TenantResolverTest`.
El filtro `tenant` quedó como filtro GLOBAL en el grupo 1 del Paso 2 (ver abajo). Falta correr `php spark migrate` en la base de desarrollo.

Plan original:
- Migración `tenants` (`id`, `slug` único, `name`, `status`, `created_at`).
- `app/Tenancy/TenantContext.php`.
- `app/Exception/Tenant/`: `TenantNotResolvedException`, `TenantNotFoundException` (404), `TenantSuspendedException` (403).
- `app/Models/Tenant/TenantModel.php`, Entity y Converter.
- `app/Filters/TenantResolver.php`: lee `X-Tenant`, carga el Tenant, completa el contexto. Va antes de `auth` en rutas privadas y públicas.

### Paso 2: `tenant_id` en las tablas (una migración por grupo)
1. `users` y `patients`, con `UNIQUE(tenant_id, dni)` y `UNIQUE(tenant_id, email)` (revisar la migración del 2026-10-02).
2. `medicals`, `specialties`, `services`, `medical_schedules`, `medical_service`.
3. `appointments` y `user_verification_tokens`.

Después de cada grupo: los Models del grupo filtran por `TenantContext::id()` y se agrega el test "el Tenant B no puede leer lo del Tenant A". El Filter `Auth` verifica que el `tenant_id` del usuario del token coincida con el del Tenant. Armar el test que detecta queries sin filtro de `tenant_id`.

#### Estado del Paso 2
- **Grupo 1 (`users`, `patients`): HECHO (06/10/2026).** Migración `AddTenantIdToUsersAndPatients` (siembra el Tenant 1 `demo`; `UNIQUE(tenant_id, email)` y `UNIQUE(tenant_id, dni)`), `UserModel` y `PatientModel` filtran por `TenantContext::id()`, filtro `tenant` global, `X-Tenant` permitido en CORS, test de aislamiento `UserPatientTenantIsolationTest`.
- **Transición acordada (decisiones del 06/10/2026):**
  - `tenant_id` lleva `DEFAULT 1` TRANSITORIO. La última migración del Paso 2 lo elimina; agregar un test que verifique que ya no existe.
  - Fallback del filtro `tenant`: sin header `X-Tenant` y fuera de producción se usa `tenant.fallbackSlug` (`.env`, `demo`). En producción nunca aplica. Eliminarlo cuando el frontend mande el header.
  - `tests/bootstrap.php` fija el Tenant 1 por defecto para toda la suite; los tests que cambian de Tenant deben restaurarlo.
- **Pendiente de este grupo:** el filtro `Auth` todavía no compara el `tenant_id` del usuario con el del header (hoy lo cubre `UserModel::findByToken`, que filtra por Tenant; revisarlo al cerrar el Paso 2).
- **Limitación conocida (documentada en el ADR 003):** el `down()` de la migración no se puede revertir si dos Tenants comparten email o DNI; lo verifica antes de escribir y falla con un error explícito, sin tocar la base.
- Pendiente aparte: [revisar el fallback `demo` cuando haya más de un Tenant](fallback-demo-con-mas-de-un-tenant.md).
- **Grupo 2 (`specialties`, `services`, `medicals`, `medical_schedules`, `medical_service`): HECHO (06/10/2026).** Migración `AddTenantIdToCatalogAndMedicalTables` (`DEFAULT 1` transitorio; `UNIQUE(tenant_id, name)` en `specialties`; `down()` con pre-check de nombres repetidos entre Tenants, igual que el grupo 1), los 5 Models filtran por `TenantContext::id()`, test `MedicalCatalogTenantIsolationTest` (8 casos).
- **Refuerzo de JOINs de los Grupos 1 y 2: hecho (06/10/2026).** 28 JOINs en `PatientModel`, `MedicalModel`, `MedicalScheduleModel` y `MedicalServiceModel` llevan `AND X.tenant_id = A.tenant_id`; test `JoinTenantIntegrityTest` (referencias cruzadas entre Tenants). Regresión de confirmación cerrada (07/10/2026).
- **Pendiente para el Grupo 3:** "En `PatientModel`, el `EXISTS` sobre `appointments` se refuerza con `AND appointments.tenant_id = patients.tenant_id` cuando `appointments` tenga `tenant_id` (Grupo 3)."
- **Plan detallado del Grupo 3:** [multitenant-grupo-3-plan.md](multitenant-grupo-3-plan.md).
- **Grupo 3 (`appointments`, `user_verification_tokens`): IMPLEMENTADO (06/10/2026), regresión final cerrada (07/10/2026).** Detalle y hallazgos en [multitenant-grupo-3-plan.md](multitenant-grupo-3-plan.md#estado-de-la-implementación). Resumen: migración `AddTenantIdToAppointmentsAndTokens`, `TenantModel::findAllActive()`, `UserTokenModel` (incluido `deleteExpired()`), `AppointmentModel` (+ `EXISTS` de `PatientModel`), `MetricsModel`, `AppointmentReminderService` + comando, seeders del dashboard con `seed.tenantSlug`. Cada uno con su test de aislamiento.
- (Texto original del Grupo 3, ya resuelto) Además de los Models propios, ahí hay que scopear lo que quedó a medias y hoy solo filtra de forma indirecta por JOIN:
  - `AppointmentModel` (consultas con JOIN a `medicals`, `patients` y `users`).
  - `MetricsModel` (toda su SQL: agregaciones sobre `appointments`, `medicals`, `users`, `patients`).
  - Los Services que usan `GET_LOCK` y cache (Paso 3).
  - `DashboardDemoSeeder` y `DashboardDemoCleanupSeeder` (insertan en `users` y `patients` sin Tenant).
- Pendiente de revisión al cerrar el Paso 2: los IDs de `medicals`, `services` y `specialties` que llegan en el body de un turno o de una asignación se validan hoy con Models ya scopeados (devuelven `null` si son de otro Tenant), pero conviene un test de punta a punta que lo confirme.

#### Cierre del Paso 2: migración `DropTenantIdDefaults` — HECHO (07/10/2026)
Migración `2026-10-07-100000_DropTenantIdDefaults` (idempotente, `down()` vuelve a `DEFAULT 1`) y `DropTenantIdDefaultsMigrationTest` (11 tests: esquema sin default en las 9 tablas, y un INSERT crudo sin `tenant_id` falla nombrando la columna). La aserción de `AddTenantIdToAppointmentsAndTokensMigrationTest` pasó a esperar default nulo. Regresión: `tests/database` completo (351 tests) en verde salvo la `F` de CORS conocida (y `tests/unit`: solo las 2 `F` de CORS). Aplicada también en la base de desarrollo. Lo que sigue pendiente de este cierre: filtro `Auth` vs `tenant_id` del header, test integrador de ids de otro Tenant en el body, y el fallback `demo` (ver arriba). Texto original:

Última migración del Paso 2, después del Grupo 3. Quita el `DEFAULT 1` de `tenant_id` en TODAS las tablas con esa columna (`users`, `patients`, `specialties`, `services`, `medicals`, `medical_schedules`, `medical_service`, `appointments`, `user_verification_tokens`). Va acompañada de su test:
- **Test "INSERT crudo sin `tenant_id` falla":** un `INSERT` de SQL directo en cada una de esas tablas, sin `tenant_id`, debe lanzar error (la columna es NOT NULL sin default). Hoy ese INSERT tendría éxito silencioso en el Tenant 1 por el default transitorio; este test es la prueba de que el default ya no existe.
- Los tests que hoy insertan filas directo (12 archivos solo para `appointments`, más los de users/patients/medicals) tienen que pasar a escribir `tenant_id` explícito o a usar un helper; hay que adaptarlos antes de correr esta migración.
- Se elimina junto con el fallback `demo` si corresponde (ver `fallback-demo-con-mas-de-un-tenant.md`).
Decisión del 06/10/2026: mientras tanto `appointments` y `user_verification_tokens` también llevan `DEFAULT 1` transitorio, y la garantía de que los Models nunca caen al default se cubre en cada Grupo con el test "el Model usa el contexto o lanza `TenantNotResolvedException`".

#### Reglas acordadas para el Grupo 3 (06/10/2026)
- Todo JOIN lleva `AND X.tenant_id = A.tenant_id`; los Grupos 1 y 2 se refuerzan con la misma regla ANTES de empezar el Grupo 3.
- `SendAppointmentRemindersCommand` recorre los Tenants activos (`TenantModel::findAllActive()`), fija y limpia `TenantContext` por cada uno y saltea los suspendidos.
- Los seeders del dashboard exigen `seed.tenantSlug` en el `.env` (abortan si falta o no existe), escriben `tenant_id` explícito leyendo `TenantContext`, y se corrige el INSERT roto de `medicals.phone`.
- Los tests de aislamiento se escriben junto con cada Model, no al final.

### Paso 3: prefijos de clave
- `public_identity_failures_{tenantId}_{sha256(dni)}` en `PublicIdentityAttemptLimiter`.
- `public_identity_{tenantId}_{dni}` en `PatientGuestResolverService`.
- `appt_lock_{medicalId}_{fecha}` no cambia (el `medicalId` es único en la base).

### Paso 4: configuración y módulos
- Migraciones `tenant_settings` (`name`, `logo_url`, `primary_color`, `reception_email`, `reception_email_enabled`, `reply_to_email`, `timezone`, `max_active_future_appointments`) y `tenant_modules`.
- `app/Constants/TenantModule.php`.
- Filter `module:{nombre}` (403 si está apagado).
- `AppointmentNotificationService` y los emails leen de `tenant_settings`; el SMTP queda en el `.env`.
- `GET /tenant/config` para el frontend.
- `max_active_future_appointments` reemplaza la constante `MAX_ACTIVE_FUTURE_APPOINTMENTS`.

### Paso 5: panel SuperAdmin (mínimo HECHO el 07/10/2026: login, listar, crear y suspender Tenants; ver `.claude/rules/platform-admin.md`. Faltan módulos/configuración y el email al ADMIN)
- Migración `platform_admins`, login propio, filtro `platformAdminOnly`.
- `POST/GET/PUT /platform/tenants`, y rutas para módulos y configuración.
- Alta de Tenant: crea el Tenant y el primer ADMIN, y le manda el email para elegir contraseña (reusando el flujo de reset-password).

### Transversal
- Helper de tests que crea un Tenant de prueba y fija el `TenantContext`.
- ~~Actualizar `CLAUDE.md` con las reglas nuevas cuando el Paso 2 esté implementado.~~ Hecho (07/10/2026): sección "Multitenant (implementado)".

## Por qué el orden
El panel va al final: hasta que el aislamiento esté probado no tiene sentido tener la herramienta para crear Tenants. El Paso 1 es el más seguro: agrega una tabla y un Filter sin tocar ninguna tabla existente.

## Riesgos
- Un `WHERE tenant_id` olvidado expone datos entre Tenants. Mitigación: `TenantContext` que falla ruidosamente y el test que detecta queries sin filtro.
- Con cache en `DummyHandler` el bloqueo por DNI no limita (ya documentado en `public-booking.md`).
- Suite lenta: resuelta (07/10/2026) con `FastDatabaseTestTrait`; la regresión de 13 archivos pasó de 50-120 min a ~14 min y `tests/database` completo (351 tests) tarda ~48 min de proceso. Pendientes en `suite-rapida-pendientes.md`.

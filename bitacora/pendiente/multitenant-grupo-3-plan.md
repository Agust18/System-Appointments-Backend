# Multitenant, Paso 2, Grupo 3: plan detallado (`appointments`, `user_verification_tokens`)

- **Estado:** plan aprobado en lo general, NO implementado. No se escribe código hasta que la regresión del refuerzo de JOINs (Grupos 1 y 2) confirme que no rompió nada.
- **Fecha:** 06/10/2026
- **Contexto:** `multitenant-plan.md` (decisiones y estado general), `adr/001`, `adr/003`.

## Estado de la implementación
**Implementado el 06/10/2026**, en el orden del plan y con el test de aislamiento de cada pieza. La regresión final (Appointment, Patient, Public, Filters, Permissions, User y la suite unitaria) estaba corriendo al escribir esto; su resultado no está registrado acá hasta que termine.

| Paso | Qué se hizo | Test | Resultado |
|---|---|---|---|
| 1 | Migración `AddTenantIdToAppointmentsAndTokens` | `AddTenantIdToAppointmentsAndTokensMigrationTest` | 4 tests, en verde |
| 2 | `TenantModel::findAllActive()` | `TenantModelTest` | 2 tests, en verde |
| 3 | `UserTokenModel` scopeado (incluido `deleteExpired()`) | `UserTokenTenantIsolationTest` | 7 tests, en verde |
| 4 | `AppointmentModel` (28 JOINs reforzados) y `EXISTS` de `PatientModel` | `AppointmentTenantIsolationTest` | 11 tests, en verde |
| 5 | `MetricsModel` (12 JOINs reforzados, filtro antes del `GROUP BY`) | `MetricsTenantIsolationTest` | 9 tests, en verde |
| 6 | `AppointmentReminderService` + comando reducido a cascarón | `AppointmentReminderServiceTest`, `SendAppointmentRemindersCommandTest` | 6 + 2 tests, en verde |
| 7 | Seeders con `seed.tenantSlug` obligatorio y `tenant_id` explícito | `DashboardSeedersTenantTest` | 7 tests, en verde |

### Hallazgos durante la implementación
- **`countProjection` y `statsByStatus` no hacen JOIN a `services`** (la lista sí): total ≠ lista. No es una fuga entre Tenants. Pendiente aparte: [count-y-stats-de-turnos-sin-join-a-services.md](count-y-stats-de-turnos-sin-join-a-services.md).
- **`UserTokenModel::deleteExpired()` no tiene llamadores hoy** (no hay job de limpieza). Quedó scopeado igual; un job que recorra los Tenants activos puede agregarse reutilizando el patrón de `AppointmentReminderService`.
- **Los seeders del dashboard están excluidos de git** (`.git/info/exclude`): los cambios (y la clave `seed.tenantSlug`) viven solo en esta copia de trabajo. `DashboardSeedersTenantTest` se saltea si el seeder no existe. El helper `app/Database/Seeds/Support/ResolvesSeedTenant.php` sí es versionable.
- **Los ids de especialidad fijos del seeder (1 y 3) cruzaban Tenants:** los ids son únicos en toda la base, así que dos Tenants no podían compartir "especialidades existentes". Ahora el seeder crea sus 4 especialidades (`[SEED] Clínica médica`, `[SEED] Cardiología`, `[SEED] Pediatría`, `[SEED] Traumatología`), cambio de demo deliberado. También se corrigió el INSERT roto de `medicals.phone`.
- **El seeder imprime "Sembrado OK" aunque los INSERT fallen en silencio** dentro de la transacción (CI4 no lanza ahí; por ejemplo, con `role_id` inexistente). Previo al multitenant, no se tocó.
- **`AppointmentModel::update()` devuelve `?Appointment` por `find()` pero declara `Appointment`:** actualizar un turno ajeno lanza `TypeError` en vez de devolver null. Hoy los Services cortan antes con 404 al no encontrar el turno; el test lo cubre.
- `TenantContext::current(): ?Tenant` se agregó para que los procesos que cambian de Tenant (recordatorios, seeders) restauren el contexto previo.

## Pendiente anotado para este grupo
> En `PatientModel`, el `EXISTS` sobre `appointments` se refuerza con `AND appointments.tenant_id = patients.tenant_id` cuando `appointments` tenga `tenant_id` (Grupo 3).

Son 2 lugares: `searchProjection` y `countProjection` (la subconsulta `SELECT 1 FROM appointments A INNER JOIN medicals M ...`). Hoy esa subconsulta cruza `medicals` por Tenant pero no `appointments`. Test: en `JoinTenantIntegrityTest`, un turno del Tenant B que apunte a un paciente del A no debe hacer que ese paciente aparezca en la búsqueda por `medicalUserId`.

## Reglas (acordadas)
- Todo JOIN lleva `AND X.tenant_id = A.tenant_id` (o contra la tabla padre inmediata). El filtro del WHERE va siempre antes de cualquier `GROUP BY`.
- `tenant_id` con `DEFAULT 1` TRANSITORIO en las dos tablas; se elimina en `DropTenantIdDefaults` (ver `multitenant-plan.md`).
- Cada Model se implementa junto con su test de aislamiento, no todos al final.
- Los Models siempre escriben `TenantContext::id()` y con el contexto vacío lanzan `TenantNotResolvedException` (nunca caen al default).

## 1. Migración `AddTenantIdToAppointmentsAndTokens` (estructura; NO aplicada)
Archivo previsto: `app/Database/Migrations/2026-10-06-130000_AddTenantIdToAppointmentsAndTokens.php`. No existe todavía: un archivo de migración se aplicaría solo en el próximo refresh de la base de test.

`up()`:
1. `ALTER TABLE appointments ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id`
2. `ALTER TABLE appointments ADD KEY idx_appointments_tenant_fecha (tenant_id, fecha)` (sirve a las métricas, que filtran por Tenant y rango de fechas, y la FK del paso 3 lo reutiliza).
3. `ALTER TABLE appointments ADD CONSTRAINT fk_appointments_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)`
4. `ALTER TABLE user_verification_tokens ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id`
5. `ALTER TABLE user_verification_tokens ADD CONSTRAINT fk_user_verification_tokens_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)`

Decisiones de diseño:
- **No cambia ningún único.** `appointments` no tiene únicos; `user_verification_tokens.token` sigue UNIQUE global (token aleatorio: dos filas con el mismo token no pueden coexistir ni entre Tenants). Por eso el `down()` no necesita pre-check de duplicados.
- **No hace falta un índice nuevo para `hasOverlap` ni la disponibilidad:** filtran por `medical_id` (único en toda la base) y `fecha`, y ya usan `idx_medical_fecha_status`; `tenant_id` se aplica como filtro residual.

`down()`: idempotente, con los mismos chequeos de existencia (`indexExists`, `columnExists`, `constraintExists`) que las migraciones de los Grupos 1 y 2. Orden: FK, índice, columna.

## 2. JOINs de `AppointmentModel` que necesitan refuerzo (28 JOINs, 8 métodos)
Alias de la tabla principal: `a`/`A` = `appointments`.

| Método | JOINs actuales | Refuerzo |
|---|---|---|
| `findByPatientUserId` | `patients p ON p.id = a.patient_id` | `AND p.tenant_id = a.tenant_id`; WHERE `a.tenant_id = ?` |
| `findByMedicalUserId` | `medicals m ON m.id = a.medical_id` | `AND m.tenant_id = a.tenant_id`; WHERE `a.tenant_id = ?` |
| `hasRelationWithPatient` | `medicals m` (dentro de `EXISTS`) | `AND m.tenant_id = a.tenant_id`; `a.tenant_id = ?` |
| `findUpcomingIn24Hours` | `medicals M`, `users U_med`, `patients P`, `users U_pac`, `services SRV` | `M`/`P`/`SRV` contra `A.tenant_id`; `U_med` contra `M.tenant_id`; `U_pac` contra `P.tenant_id`; WHERE `A.tenant_id = ?` |
| `findProjection` | los mismos 5 | idem |
| `searchProjection` | los mismos 5 | idem; el WHERE sale de `buildWhereClause` |
| `countProjection` | los 5 + `specialties S` | idem + `S` contra `M.tenant_id` |
| `statsByStatus` | `medicals`, `users U_med`, `patients`, `users U_pac` | idem; el `GROUP BY A.status` ya va después del WHERE |

Sin JOIN, solo `tenant_id = ?` (10 métodos): `insert` (escribe `TenantContext::id()`), `update`, `find`, `delete`, `hasOverlap`, `countActiveFutureByPatient`, `findByMedicalId`, `findByPatientId`, `findActiveHoursByMedicalAndFecha` y `findActiveHoursByMedicalAndFechaRange`.

`buildWhereClause()` (hoy `WHERE 1 = 1`) pasa a `WHERE A.tenant_id = ?` con el parámetro primero: cubre `searchProjection`, `countProjection` y `statsByStatus` con un solo cambio.

## 3. JOINs de `MetricsModel` que necesitan refuerzo (12 JOINs, 7 consultas)
Todas parten de `appointments A`. El filtro va en el WHERE, antes de cualquier `GROUP BY`.

| Consulta | WHERE | JOINs a reforzar |
|---|---|---|
| `getKpis` | `buildDateMedicalWhere` | `LEFT JOIN services SRV` (`AND SRV.tenant_id = A.tenant_id`); `LEFT JOIN medical_service MS ON MS.medical_id = A.medical_id AND MS.service_id = A.service_id` (`AND MS.tenant_id = A.tenant_id`; define el `revenue`) |
| `getByMedical` | WHERE propio: `A.tenant_id = ? AND A.fecha BETWEEN ? AND ?` | `medicals M`, `users U` |
| `getMonthlyTrend` | `buildDateMedicalWhere` | ninguno |
| `getTopServices` | `buildDateMedicalWhere` | `services SRV` |
| `getTopSpecialties` | `buildDateMedicalWhere` | `medicals M`, `specialties SP` |
| `getScheduleHeatmap` | `buildDateMedicalWhere` | ninguno (su `HAVING COUNT(*) > 0` no lleva filtro de Tenant) |
| `getRecentAppointments` | `buildDateMedicalWhere` | `patients P`, `users U_pac`, `medicals M`, `users U_med`, `LEFT JOIN services SRV` |

`buildDateMedicalWhere()` pasa a `WHERE A.tenant_id = ? AND A.fecha BETWEEN ? AND ?` y cubre 6 de las 7 consultas.

## 4. Otros archivos del Grupo 3
- `UserTokenModel` (6 métodos): `insert` escribe `tenant_id`; `findByToken`, `findActiveByUserAndType`, `markAsUsed`, `deleteByUserAndType` filtran por Tenant. **`deleteExpired()` es una operación de mantenimiento entre Tenants** (`DELETE ... WHERE expires_at < NOW()`): decisión pendiente de confirmar. Propuesta: scopearlo igual (consistencia, y el que lo llame ya tendrá contexto) y que el job de limpieza recorra los Tenants activos como el de recordatorios.
- `TenantModel::findAllActive(): Tenant[]` (`WHERE status = 'active' ORDER BY id`).
- `SendAppointmentRemindersCommand`: recorre los Tenants activos, fija y limpia `TenantContext` por cada uno y saltea los suspendidos. Hoy no es testeable (instancia `EmailSenderService` adentro). **Propuesta, requiere OK:** extraer la lógica a `AppointmentReminderService` con `?callable $sender` como punto de observación en tests, igual que `AppointmentNotificationService` (ver `email.md`); el comando queda como un cascarón que llama al Service por cada Tenant.
- `DashboardDemoSeeder`: exige `seed.tenantSlug` (`.env`), aborta si falta o no existe, fija el `TenantContext` y escribe `TenantContext::id()` explícito en sus 7 INSERT (`specialties`, `services`, `medicals`, `medical_service`, `patients`, `appointments`, `users`). Se corrige de paso el INSERT de `medicals` que usa la columna `phone`, eliminada por `MovePhoneFromMedicalsToUsers`.
- `DashboardDemoCleanupSeeder`: sus 3 `DELETE` (`users` por `%@seed.local`, `services` y `specialties` por `[SEED] %`) llevan `AND tenant_id = ?` con el mismo `seed.tenantSlug`; sin eso borraría el seed de todos los Tenants.
- `.env.example`: documentar `seed.tenantSlug`.

## Decisiones confirmadas (06/10/2026)
- **Recordatorios:** la lógica se extrae a `AppointmentReminderService` con `?callable $sender` inyectable (como `AppointmentNotificationService`). El comando solo recorre los Tenants activos y fija/limpia `TenantContext` por cada uno.
- **`UserTokenModel::deleteExpired()`:** SCOPEADO por Tenant, igual que el resto de los métodos del Model. No se deja global como excepción. El job de limpieza que recorre los Tenants activos puede agregarse ahora o después; el método en sí debe estar scopeado desde el Grupo 3.

## 5. Orden de trabajo (cada Model con su test, no todos al final)
0. **Condición previa:** regresión de Patient, Medical y Appointment en verde tras el refuerzo de JOINs de los Grupos 1 y 2.
1. Migración `AddTenantIdToAppointmentsAndTokens` + `MigrationTenantIdAppointmentsTokensTest` (up deja las columnas y FK; `down()` reintentable).
2. `TenantModel::findAllActive()` + test (excluye suspendidos).
3. `UserTokenModel` + `UserTokenTenantIsolationTest` (un token del Tenant A no se encuentra, no se marca usado ni se borra desde el B; insert usa el contexto; sin contexto lanza).
4. `AppointmentModel` (+ refuerzo del `EXISTS` de `PatientModel`) + `AppointmentTenantIsolationTest`.
5. `MetricsModel` + `MetricsTenantIsolationTest`.
6. Recordatorios: `AppointmentReminderService`, comando y `AppointmentReminderTenantTest`.
7. Seeders + `DashboardSeederTenantTest`.
8. Regresión del Grupo 3 (Appointment, Patient, Public, Filters, Permissions y suite unitaria) y actualización de este plan y del README.

Después del Grupo 3 queda, a revisión conjunta: **`DropTenantIdDefaults`** (Paso 2 final, obligatorio) y recién ahí el Paso 3 (prefijos de clave de cache y locks).

## 6. Tests de aislamiento que pidió el dueño del proyecto
1. **Un turno del Tenant 1 no aparece en las listas del Tenant 2:** `searchProjection`, `countProjection`, `statsByStatus`, `find`, `findProjection`, `findByMedicalId`, `findByPatientId`, `findByPatientUserId`, `findByMedicalUserId`, `hasRelationWithPatient`, `hasOverlap`, `countActiveFutureByPatient`, `findActiveHours*` y `findUpcomingIn24Hours`. `update` y `delete` sobre un turno ajeno no hacen nada.
2. **Las métricas del Tenant 1 no incluyen datos del Tenant 2:** datos distintos en cada Tenant (por ejemplo 2 turnos con precio personalizado en el A y 5 en el B); las 7 consultas, incluido el `revenue` y los rankings, devuelven solo lo propio.
3. **Un INSERT sin `tenant_id` en el Model usa el contexto o falla, nunca el default:** con contexto B, el INSERT queda en B; con el contexto vacío, lanza `TenantNotResolvedException`. El caso "INSERT crudo sin `tenant_id` falla" depende de que el default ya no exista y queda en `DropTenantIdDefaults` (ver `multitenant-plan.md`).
4. **Integridad de JOINs con referencias cruzadas** (turno del A que apunta a un médico, paciente o servicio del B), igual que `JoinTenantIntegrityTest` de los Grupos 1 y 2.

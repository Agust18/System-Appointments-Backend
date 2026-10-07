# Multitenant: traspaso para el hilo siguiente

- **Fecha:** 06/10/2026 (hilo cerrado a mitad de contexto). **Actualizado el 07/10/2026: la suite lenta está resuelta, el Paso 1 (adaptar tests) y el Paso 2 (`DropTenantIdDefaults`) están cerrados.** Lo que sigue es el Paso 3 del plan (prefijos de clave). Las secciones 0 y "Estado del paso 1" quedan como historial.
- **Rama:** `Notificaciones`. **Nada de esto está commiteado** (ver "Estado del repositorio").
- **Léelo junto con:** `multitenant-plan.md` (decisiones y estado general), `multitenant-grupo-3-plan.md` (detalle del Grupo 3), `../glosario.md` y `../adr/001`, `002`, `003`.
- **Orden de trabajo acordado el 06/10/2026 y su resultado (07/10/2026):**
  1. ~~Arreglar la suite lenta~~ — **resuelto** (sección 0).
  2. ~~Retomar la regresión del Paso 1~~ — **cerrado**: los 13 archivos en verde (125 tests), solo la `F` de CORS conocida.
  3. ~~`DropTenantIdDefaults`~~ — **hecho**: migración, `DropTenantIdDefaultsMigrationTest` y regresión de `tests/database` (351 tests) en verde salvo la `F` de CORS conocida; `tests/unit` solo con las 2 `F` de CORS. Aplicada en la base de desarrollo.

## 0. RESUELTO (07/10/2026): la suite de tests se había vuelto inviable de correr

**Resolución:** `tests/_support/Database/FastDatabaseTestTrait.php` reemplaza a `DatabaseTestTrait` en 33 archivos de test (solo cambian 2 líneas de encabezado por archivo; no se tocó ninguna migración ni ninguna aserción). Hace rollback + migrate completo una vez por clase y cada vez que la huella del esquema (columnas, tipos, nulabilidad, índices) no coincide; entre tests solo vacía los datos (`TRUNCATE` con las FK apagadas) y reinserta el Tenant 1 `demo`. Las 3 clases que alteran el esquema a mano conservan `DatabaseTestTrait` con refresh completo. Resultado medido: muestra de 8 clases con tests y aserciones idénticos y un 30-45 % del tiempo; regresión de 13 archivos de 50-120 min a ~14 min; prueba en orden aleatorio (56 tests, semilla 20261007, con clases de esquema en medio) en verde. Pendientes: [suite-rapida-pendientes.md](suite-rapida-pendientes.md).

**Texto original del problema (historial):**

Sin arreglar esto, cada regresión tarda horas y el Paso 2 de `DropTenantIdDefaults` es inviable (hay que correr la regresión al menos dos veces más).

**Qué se midió (06/10/2026)**
- Cada test de base de datos (`DatabaseTestTrait` con `$refresh = true`) **revierte y vuelve a correr todas las migraciones**. El costo por test creció con cada grupo del multitenant: **~9 s** (con solo `tenants`) → 11-17 s (Grupo 1) → 17-20 s (Grupo 2) → **25-57 s** (Grupo 3). El `CLAUDE.md` dice que la suite completa tarda ~44 minutos: hoy tarda varias veces más.
- Causa probable: las 4 migraciones del multitenant suman ~35 sentencias `ALTER TABLE` (`AddTenantIdToUsersAndPatients` 16, `…AppointmentsAndTokens` 10, `…CatalogAndMedicalTables` 8, `CreateTenantsTable` 1), y las que agregan claves foráneas reconstruyen tablas (se vio `rename result table` en el `processlist`). **No se hizo un perfil**: la causa se infiere del recuento de sentencias y de la evolución de los tiempos. El primer paso es medir cuánto tarda el rollback, la migración y el cuerpo del test por separado.
- Agravante: la máquina (VM de Docker) se suspende. Ese día el contenedor llevaba 14 h arriba y la VM solo estuvo despierta ~7,6 h (459 min de uptime). Los tiempos de reloj no son confiables; medir con `ps -eo etime` del proceso.

**Ideas a evaluar (ninguna aplicada ni decidida)**
1. **Migrar una sola vez por clase** (`$migrateOnce`) o por corrida, y limpiar los datos entre tests con un `TRUNCATE` de las tablas (con `FOREIGN_KEY_CHECKS=0`) en vez de reconstruir el esquema. Los tests que manipulan el esquema a mano (`PatientSchemaMigrationTest`, `NormalizeUserIdentityMigrationTest`, `AddTenantIdToAppointmentsAndTokensMigrationTest`, los de `down()`) y los que usan transacciones o `GET_LOCK` tendrían que conservar el refresh completo.
2. **Abaratar el DDL de las migraciones:** apagar `foreign_key_checks` mientras se arma la base de test, usar `ALGORITHM=INPLACE, LOCK=NONE` y juntar varios `ALTER` sobre la misma tabla en una sola sentencia. Las 4 migraciones del multitenant **todavía no se aplicaron fuera de `turnero_test`**, así que se pueden reescribir sin riesgo de datos ya migrados.
3. Combinación de 1 y 2.
Existe además `suite-lenta-refactor-tests.md` (pendiente previo); conviene unificarlos.

**Criterio de éxito:** un test de base de datos cae a pocos segundos y la regresión del Paso 1 (13 archivos) toma minutos, sin cambiar ningún resultado (mismas 3 `F` de CORS y nada más).

## 1. Estado actual del multitenant

### Pasos del plan (`multitenant-plan.md`)
| Paso | Qué es | Estado |
|---|---|---|
| 1 | Tenant resuelto en cada request (`tenants`, `TenantContext`, filtro `tenant`) | HECHO |
| 2, Grupo 1 | `users`, `patients` con `tenant_id` | HECHO |
| 2, Grupo 2 | `specialties`, `services`, `medicals`, `medical_schedules`, `medical_service` | HECHO |
| 2, refuerzo de JOINs (Grupos 1 y 2) | 28 JOINs con `AND X.tenant_id = A.tenant_id` | HECHO |
| 2, Grupo 3 | `appointments`, `user_verification_tokens`, Métricas, recordatorios, seeders | HECHO |
| 2, cierre | **`DropTenantIdDefaults`** (quitar el `DEFAULT 1` de las 9 tablas) | HECHO (07/10/2026, sección 2) |
| 3 | Prefijos de clave de cache y locks por Tenant (`public_identity_*`) | PENDIENTE |
| 4 | Configuración por Tenant (`tenant_settings`) y módulos (`tenant_modules`, filtro `module:`) | PENDIENTE |
| 5 | Panel SuperAdmin (`platform_admins`, rutas `/platform/*`) | PENDIENTE |

Numeración: el **Paso 3 son los prefijos de clave**; el **panel de administración es el Paso 5**.

### Resultado de la regresión (cerrada)
Todas las carpetas con tests de base de datos más la suite unitaria completa, en dos corridas (`Appointment`, `Patient`, `Public`, `Filters`, `Permissions`, `User`, `unit`, y después `Medical`, `Tenant`, `Metrics`, `Commands`, `Seeds`). **Las únicas fallas son exactamente 3, ya documentadas** en `tests-cors-dependen-del-env-local.md` (tu `.env` local tiene `cors.allowedOrigins`):
1. `CorsConfigTest::testWithoutConfiguredOriginsNoOriginIsAllowed` (`tests/unit/Config/CorsConfigTest.php:45`)
2. `CorsConfigTest::testPatternsAreReadFromTheEnvironment` (`tests/unit/Config/CorsConfigTest.php:72`)
3. `PublicBookingEndpointsTest::testWithoutConfiguredOriginsNoCorsHeadersAreSent` (`tests/database/Public/PublicBookingEndpointsTest.php:425`)

Nunca se corrió la suite completa de una sola vez (el `CLAUDE.md` lo prohíbe sin pedirlo): fue carpeta por carpeta.

## 2. `DropTenantIdDefaults` — HECHO (07/10/2026)
Migración `2026-10-07-100000_DropTenantIdDefaults`, test `tests/database/Tenant/DropTenantIdDefaultsMigrationTest.php` (11 tests) y regresión en verde (ver el encabezado). La aserción de default `'1'` de `AddTenantIdToAppointmentsAndTokensMigrationTest` pasó a esperar default nulo. Quedó aplicada en la base de desarrollo. El resto de esta sección es el plan original.

Quita el `DEFAULT 1` de `tenant_id` en las 9 tablas: `users`, `patients`, `specialties`, `services`, `medicals`, `medical_schedules`, `medical_service`, `appointments`, `user_verification_tokens`. En producción es seguro: todas las filas ya tienen `tenant_id` (NOT NULL), solo cambia el esquema (`ALTER COLUMN tenant_id DROP DEFAULT`, de metadatos en MySQL 8). El código de la app ya escribe `tenant_id` explícito en todos los INSERT. Decisión de orden: **dos pasos, para aislar fallas**.

### Paso 1: adaptar los tests (con el default todavía puesto)
Agregar `'tenant_id' => 1` a cada INSERT de test que lo omita (opción A, mecánica). Alcance medido: unas 150 llamadas en 26 archivos (`users` 40, `patients` 30, `specialties` 19, `medicals` 17, `appointments` 17, `services` 10, `medical_schedules` 8, `medical_service` 6, `user_verification_tokens` 3). Debe quedar todo verde con el default presente; si algo falla acá, es de la adaptación y no de la migración. Después, regresión.
**Estado del paso 1: ver la sección "Estado del paso 1" al final de este archivo (se actualiza al cerrarlo).**

### Paso 2: la migración y su test
- `app/Database/Migrations/<fecha>_DropTenantIdDefaults.php`, idempotente (chequear antes de tocar), con `down()` que vuelve a `SET DEFAULT 1`.
- **Test de inspección de esquema (la prueba principal):** `SHOW COLUMNS` en las 9 tablas; el `Default` de `tenant_id` debe ser nulo. No depende del modo SQL del servidor.
- **Test "INSERT crudo sin `tenant_id` falla":** por cada tabla, un INSERT sin `tenant_id` debe lanzar error y el mensaje debe nombrar `tenant_id`. Aunque un servidor no estuviera en modo estricto, el valor implícito sería 0 y la FK a `tenants` lo rechazaría igual.
- Verificar con `grep` que no queden `INSERT INTO` de esas tablas en `app/` sin `tenant_id` (migraciones, seeders).
- Correr de nuevo la regresión de los módulos afectados.

## 3. Decisiones tomadas en este hilo
**Modelo (ADR y glosario)**
- **Tenant** = organización cliente de la plataforma; término genérico, no atado a salud ni al rubro (`tenant_id`, nunca `clinic_id`). Sin sedes por ahora. Glosario en `bitacora/glosario.md`.
- Una sola base con `tenant_id` (ADR 001). Subdominio `{slug}.dominio` y header `X-Tenant` hacia la API (ADR 002). Usuarios separados por Tenant, SuperAdmin en tabla aparte `platform_admins`, una sola API con `/platform/*` (ADR 003).
- SMTP único de plataforma; cada Tenant configura solo nombre y `reply_to`. Núcleo siempre activo; módulos con toggle: `public_booking`, `metrics`, `email_notifications`. Tenant suspendido: 403 en todas sus rutas, datos conservados.

**Implementación**
- `TenantContext` estático que **falla** (`TenantNotResolvedException`) si nadie lo completó. Cada Model agrega `tenant_id = ?` explícito; el filtro va en el `WHERE`, siempre antes de cualquier `GROUP BY`.
- **Regla de JOIN:** todo JOIN lleva `AND X.tenant_id = A.tenant_id` (contra la tabla padre inmediata), porque las FK solo comparan ids y no impiden referencias cruzadas entre Tenants.
- `down()` de las migraciones con pre-check: si hay datos que impiden revertir (emails/DNI repetidos entre Tenants, nombres de especialidad repetidos), fallan limpio y sin escribir nada; son reintentables. Limitación documentada en el ADR 003.
- `UserTokenModel::deleteExpired()` scopeado por Tenant (hoy sin llamadores).
- Recordatorios: lógica en `AppointmentReminderService` con `?callable $sender` inyectable; el comando recorre los Tenants activos, fija y restaura `TenantContext` por cada uno y saltea los suspendidos (`TenantModel::findAllActive()`).
- Seeders del dashboard: `seed.tenantSlug` obligatorio (abortan si falta o no existe), `tenant_id` explícito, y crean todas sus especialidades (antes usaban ids fijos que cruzaban Tenants).
- Cada Model se implementó junto con su test de aislamiento, no todos al final.

**Transición (transitoria, a eliminar)**
- `DEFAULT 1` en `tenant_id` (las 9 tablas) para que la suite siguiera en verde grupo por grupo → lo elimina `DropTenantIdDefaults`.
- Fallback del filtro `tenant`: sin `X-Tenant` y fuera de producción usa `tenant.fallbackSlug` (`demo`); en producción responde 400. Los preflight `OPTIONS` pasan sin Tenant; `X-Tenant` está permitido en CORS.
- `tests/bootstrap.php` fija el Tenant 1 (`demo`) por defecto para toda la suite; los tests que cambian de Tenant deben restaurarlo.

## 4. Archivos clave

**Migraciones** (`app/Database/Migrations/`): `2026-10-06-100000_CreateTenantsTable`, `…-110000_AddTenantIdToUsersAndPatients`, `…-120000_AddTenantIdToCatalogAndMedicalTables`, `…-130000_AddTenantIdToAppointmentsAndTokens`.

**Código**: `app/Tenancy/TenantContext.php`, `app/Filters/TenantResolver.php` (registrado como `tenant` y global en `app/Config/Filters.php`), `app/Services/Tenant/TenantResolverService.php`, `app/Models/Tenant/TenantModel.php` (+ Entity y Converter), `app/Constants/TenantStatus.php`, `app/Exception/Tenant/`, `app/Services/Appointment/AppointmentReminderService.php`, `app/Commands/SendAppointmentRemindersCommand.php`, `app/Config/Cors.php` (`X-Tenant`), `app/Database/Seeds/Support/ResolvesSeedTenant.php`. Models scopeados: `User`, `Patient`, `Specialty`, `Service`, `Medical`, `MedicalSchedule`, `MedicalService`, `Appointment`, `Metrics`, `UserToken`.

**Configuración de tests**: `tests/bootstrap.php`, `phpunit.xml.dist` (bootstrap propio y `tenant.fallbackSlug`), `.env.example` (`tenant.fallbackSlug`, `seed.tenantSlug`).

**Tests nuevos** (todos escriben `tenant_id` explícito): `tests/unit/Tenancy/TenantContextTest`; `tests/database/Filters/TenantResolverTest`; `…/User/UserPatientTenantIsolationTest` (10), `UserTokenTenantIsolationTest` (7); `…/Medical/MedicalCatalogTenantIsolationTest` (8); `…/Tenant/JoinTenantIntegrityTest` (4), `AddTenantIdToAppointmentsAndTokensMigrationTest` (4), `TenantModelTest` (2); `…/Appointment/AppointmentTenantIsolationTest` (11), `AppointmentReminderServiceTest` (6); `…/Metrics/MetricsTenantIsolationTest` (9); `…/Commands/SendAppointmentRemindersCommandTest` (2); `…/Seeds/DashboardSeedersTenantTest` (7). **Tests existentes modificados**: `NormalizeUserIdentityMigrationTest` (ahora valida `uq_users_tenant_dni`), `CorsConfigTest` (`X-Tenant` en `allowedHeaders`).

**Documentación** (`bitacora/`): `glosario.md`; `adr/001-aislamiento-una-base-con-tenant-id.md`, `002-tenant-por-subdominio-y-header.md`, `003-usuarios-por-tenant-y-api-unica.md`; `pendiente/multitenant-plan.md`, `multitenant-grupo-3-plan.md`, este archivo; `CLAUDE.md` (una línea que apunta a esto; la regla propia y la prohibición de consultar tablas con `tenant_id` sin filtro **se agregan al cerrar el Paso 2**).

## 5. Pendientes abiertos
- **Desarrollo:** ~~falta correr `php spark migrate`~~ **hecho** (al 07/10/2026 `turnero_db` tiene las 15 migraciones, incluida `DropTenantIdDefaults`). La base de desarrollo se recreó porque un script de medición apuntó a la base equivocada (el `.env` pisó las variables). Se anotó como lección operativa (sección 7). Texto original: falta correr `php spark migrate` en la base de **desarrollo** (4 migraciones nuevas; solo se aplicaron en `turnero_test`). Dentro del contenedor: `docker exec turnero_app sh -c "cd /var/www/html && php spark migrate"`. Con el filtro global y el fallback `demo`, la API de desarrollo lo necesita.
- **Fallback `demo`** con más de un Tenant: [fallback-demo-con-mas-de-un-tenant.md](fallback-demo-con-mas-de-un-tenant.md). Fuera de `DropTenantIdDefaults` (supuesto a confirmar).
- **Bug de producción (Paso 4):** [frontend-url-por-tenant.md](frontend-url-por-tenant.md): los emails de verificación y reset arman el link con un único `FRONTEND_URL`.
- **Borrado lógico vs físico:** [borrado-logico-vs-fisico.md](borrado-logico-vs-fisico.md): auditoría post-multitenant, para otra sesión.
- **Conteo y estadísticas de turnos sin JOIN a `services`:** [count-y-stats-de-turnos-sin-join-a-services.md](count-y-stats-de-turnos-sin-join-a-services.md) (total ≠ lista; no es fuga entre Tenants).
- **Tests de CORS dependen del `.env` local:** [tests-cors-dependen-del-env-local.md](tests-cors-dependen-del-env-local.md) (las 3 `F` conocidas).
- **Paso 3** (prefijos de clave): `public_identity_failures_{tenantId}_…` y el lock `public_identity_{tenantId}_{dni}`. `appt_lock_{medicalId}_{fecha}` no los necesita.
- **Paso 4:** `reception.email` y `reception.emailEnabled` siguen siendo globales (`.env`) en `AppointmentNotificationService`; pasan a `tenant_settings`.
- **Filtro `Auth`:** no compara el `tenant_id` del usuario con el del header de forma directa; hoy lo cubre `UserModel::findByToken`, que filtra por Tenant. Revisar al cerrar el Paso 2.
- **Test de punta a punta** de que los ids de médico/servicio/especialidad en el body de un turno o asignación de otro Tenant se rechazan (hoy lo cubren los Models scopeados, no hay un test integrador).
- **Seeders del dashboard** (excluidos de git en `.git/info/exclude`, solo locales): los cambios y `DashboardSeedersTenantTest` (que se saltea si el seeder no existe) viven solo en esta copia. El seeder imprime "Sembrado OK" aunque los INSERT fallen en silencio dentro de la transacción (previo al multitenant).
- **`AppointmentModel::update()`** declara `Appointment` pero devuelve `find()` (nullable): actualizar un turno ajeno lanza `TypeError` en vez de null; hoy los Services cortan antes con 404.

## 6. Estado del repositorio
- **Nada está commiteado.** Hay ~55 cambios entre modificados y sin versionar (lista con `git status`).
- Cambios que **no son de este hilo** y que no se deben tocar ni atribuir al multitenant: `app/Config/Routes.php` (solo 55 líneas en blanco eliminadas, sin cambio funcional) y `bitacora/pendiente/analisis-servicios-appointment.md` (nota sobre los servicios de Appointment).
- Antes de commitear conviene separar en commits por tema (migraciones y código, tests, docs).

## 7. Lecciones operativas (para no repetir tropiezos)
- Los tests de base de datos van **dentro del contenedor** y **de a una corrida a la vez**: comparten `turnero_test` y se pisan. Cada test reconstruye la base (migraciones completas), de 15 a 60 segundos por test. Una carpeta grande (Appointment, 106 tests) puede tardar más de una hora.
- El reloj del sistema salta (la máquina o Docker se pausan): medir el tiempo con el proceso (`ps -eo etime`) y no con `date`.
- Para ver progreso real, lanzar las corridas largas escribiendo la salida **sin** `| tail` a un archivo y contar los puntos.
- `sed` con barras invertidas (`use App\Foo;`) falló varias veces: para insertar líneas `use`, usar la herramienta de edición.
- Un helper de test no puede llamarse `run()` (PHPUnit lo define `final`); un fatal así aborta **toda** corrida que cargue ese archivo.
- Dentro de una transacción, CI4 no lanza ante un INSERT fallido: puede quedar medio escrito en silencio.
- Para **detener** una corrida larga hay que parar primero el bucle del host (si no, sigue con el archivo siguiente) y recién después `pkill -f vendor/bin/phpunit` dentro del contenedor. Una interrupción durante el refresh de la base puede dejarla a medias: después de interrumpir, verificar `migrations` y las columnas/FK de `tenant_id` (esta vez quedó íntegra).
- Un test que pasa no prueba que detecte el problema: se comprobó con una mutación en `MedicalServiceModel`, no en todos los Models.
- **Scripts de medición o limpieza: nunca contra la base por defecto.** El `.env` del proyecto pisa las variables de entorno (`docker exec -e database.default.database=...` no alcanza), así que un script que borra tablas puede caer en `turnero_db`. Conectar con credenciales explícitas y abortar si `SELECT DATABASE()` no es la esperada antes de cualquier DDL.
- Los tests que leen `env()` deben aislar también `getenv()`, no solo `$_ENV` y `$_SERVER` (ver `tests-cors-dependen-del-env-local.md`).

## Estado del paso 1 (`'tenant_id' => 1` en los tests) — CERRADO (07/10/2026)
**Edición hecha (06/10/2026) y regresión de confirmación completa (07/10/2026):** los 13 archivos que faltaban pasaron (125 tests, 868 s) con la suite rápida, solo con la `F` de CORS conocida de `PublicBookingEndpointsTest`. Lo que sigue es el historial de cómo quedó interrumpida.

**Historial: la regresión de confirmación se interrumpió a mitad (por la suspensión de la máquina; se detuvo a propósito).**

**Estado exacto al interrumpirla (07/10/2026)**
- **14 de 27 archivos terminados, todos en verde** (115 tests). **Sin ninguna `F` ni `E` en todo el log.**
- Terminados: `Appointment/` (los 9 archivos: AvailabilityBatchService, AvailabilityService, BookingService, CreateValidatorService, DeleterService, NotificationService, ProjectionFinderService, UpdateBookingService, PublicAppointmentCreateValidatorService, PublicAppointmentCreatorService) y de `Filters/`: `AppointmentPermissionTest` (8), `AppointmentViewPermissionTest` (5), `AuthTest` (4), `MedicalViewPermissionTest` (9). Con eso queda confirmado que la clase base compartida `BookingServiceTestCase` funciona (la usan `AppointmentBookingServiceTest` y `AppointmentNotificationServiceTest`).
- **Interrumpido:** `Filters/PatientViewPermissionTest` (se detuvo a los pocos segundos de empezar; no se sabe el resultado).
- **Base de test verificada tras interrumpir:** íntegra. 4 migraciones del multitenant registradas, las 9 columnas `tenant_id` con sus 9 FK, índices `uq_users_tenant_email` y `uq_users_tenant_dni` presentes, ningún único global viejo, Tenant `demo` presente. (Hay 15 migraciones registradas porque la 15.ª es `2020-02-22-222222_example_migration` de `tests/_support`, que crea la tabla `factories`; no es del proyecto.)

**Qué se hizo**
- 26 archivos de tests adaptados: 107 llamadas `->table('X')->insert([...])` con `'tenant_id' => 1` como primera clave (cambio mecánico con `sed`) y 1 `INSERT INTO users (...)` crudo editado a mano (`PatientGuestResolverServiceTest`, columna y parámetro `tenant_id`). Las 2 cadenas multilínea que parecían inserts (`PublicBookingEndpointsTest`) eran `->update(...)` y no se tocaron.
- **Verificación estática:** un script PHP que parsea cada llamada cuenta **125 INSERT** de las 9 tablas en todos los tests, **125 con `tenant_id`** como primera clave o columna y **0 sin él**; los 26 archivos pasan `php -l`. Todos los demás `->insert(` de los tests son inserts vía Models (que ya escriben `TenantContext::id()`) o un seeder de ejemplo de `_support`. (La cifra de ~150 que se dio antes era de un `grep` más amplio; vale esta.)
- **Clase base compartida:** `BookingServiceTestCase` (abstracta, adaptada) la heredan `AppointmentBookingServiceTest` y `AppointmentNotificationServiceTest`, que **no** tienen cambios propios pero usan sus helpers: están incluidos en la regresión.

**Regresión de confirmación: 27 archivos** (los 26 adaptados + los 2 que heredan, sin contar la clase base abstracta), uno por uno, con el `DEFAULT 1` todavía puesto. Resultado esperado: todo verde salvo las 3 `F` de CORS ya documentadas (`PublicBookingEndpointsTest::testWithoutConfiguredOriginsNoCorsHeadersAreSent` es la única de ellas dentro de estos archivos). **Cualquier otra `F` o `E` significa que la adaptación rompió algo y hay que investigarla antes de seguir al Paso 2.**

**Cómo retomar: faltan 13 archivos (unos 121 tests)**, en este orden de riesgo creciente de la adaptación:
```
tests/database/Filters/PatientViewPermissionTest.php        (interrumpido: repetir entero)
tests/database/Medical/MedicalCreatorServiceTest.php
tests/database/Medical/MedicalProjectionFinderServiceTest.php
tests/database/Patient/PatientCreatorServiceTest.php
tests/database/Patient/PatientGuestResolverServiceTest.php   (aquí se editó el INSERT crudo a mano)
tests/database/Patient/PatientModelMedicalFilterTest.php
tests/database/Patient/PatientProjectionFinderServiceTest.php
tests/database/Patient/PatientPublicRegisterServiceTest.php
tests/database/Patient/PatientSchemaMigrationTest.php        (corre migraciones a mano)
tests/database/Patient/PatientUpdaterServiceTest.php
tests/database/Permissions/PermissionsServiceTest.php
tests/database/Public/PublicBookingEndpointsTest.php         (aquí se espera la F de CORS conocida)
tests/database/User/NormalizeUserIdentityMigrationTest.php   (corre migraciones a mano)
```
Correrlos **de a uno por vez** (comparten `turnero_test`), dentro del contenedor y con la salida sin filtrar a un archivo para ver el progreso: `docker exec turnero_app sh -c "cd /var/www/html && vendor/bin/phpunit <archivo>" >> <log>`. Cuando estén los 13 en verde (salvo la `F` de CORS), el Paso 1 queda cerrado y se puede pasar al Paso 2.

**Sigue sin verificarse:** nada de lo anterior corrió con el `DEFAULT 1` quitado; eso es el Paso 2.

**Qué NO se hizo todavía (Paso 2 de `DropTenantIdDefaults`):** la migración y su test. No existen aún; no se escribió ningún archivo de migración para evitar que el refresh de la base de test la aplique.

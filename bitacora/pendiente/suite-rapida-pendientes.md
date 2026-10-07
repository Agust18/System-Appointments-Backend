# Suite rápida: pendientes tras `FastDatabaseTestTrait`

- **Estado:** ⏳ Pendiente (la mejora principal ya está hecha)
- **Repo:** backend
- **Fecha:** 07/10/2026
- **Relacionado:** `multitenant-traspaso.md` (sección 0), `suite-lenta-refactor-tests.md`

## Qué se hizo
`tests/_support/Database/FastDatabaseTestTrait.php` reemplaza a `DatabaseTestTrait` en 33 archivos (los 32 que lo usaban directo más `BookingServiceTestCase`, que cubre a sus 8 subclases). Rollback + migrate completo una vez por clase y cada vez que la huella del esquema (columnas, tipos, nulabilidad, índices) no coincide; entre tests solo `TRUNCATE` de datos y reinserción del Tenant 1 (`demo`). Las 3 clases que alteran el esquema a mano conservan `DatabaseTestTrait` y refresh completo (`PatientSchemaMigrationTest`, `NormalizeUserIdentityMigrationTest`, `AddTenantIdToAppointmentsAndTokensMigrationTest`). No se tocó ninguna migración.

Resultado: la regresión de 13 archivos (125 tests) pasó de 50-120 min a ~14 min.

## Pendiente 1: truncar solo las tablas con filas
Hoy cada test hace `TRUNCATE` de ~14 tablas (DDL en MySQL): unos 4-5 s por test, más el refresh de ~25 s por clase. Truncar solo las tablas que tienen filas bajaría ese costo, pero cambia cómo se reinician los `AUTO_INCREMENT` (una tabla vacía que ya consumió ids no se reiniciaría), así que hay que comprobar que ningún test dependa de ids que empiezan en 1.

## Pendiente 2: mutación en `MedicalServiceModel`
Repetir la mutación del hilo anterior (quitar el filtro de `tenant_id` en `MedicalServiceModel`) y comprobar que los tests de aislamiento siguen fallando con la suite rápida. Los tests están verdes, pero un test verde no prueba que detecte el problema.

# Crear RolesSeeder

Hoy los roles (admin, patient, medical, reception) no se siembran. Hay que insertarlos a mano después de migrar. Un seeder los crearía en el deploy.

- **Por qué importa:** `POST /platform/tenants` crea el primer ADMIN de un Tenant con `role_id = 1` (FK a `roles`). Sin la fila `admin` en `roles`, el alta falla y hace rollback del Tenant.
- **Ids:** deben coincidir con `app/Constants/Role.php` (ADMIN=1, MEDICAL=2, PATIENT=3, RECEPTION=4).
- **Idempotente:** que se pueda correr varias veces sin duplicar (los ids son fijos).
- **Tests:** los tests de base de datos siembran el rol que necesitan a mano, porque `FastDatabaseTestTrait` vacía `roles` entre tests. No dependen del seeder.
- **Anotado el 07/10/2026** durante el Platform Admin (Paso 5 del multitenant).

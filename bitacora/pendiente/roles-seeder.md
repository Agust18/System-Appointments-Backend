# Crear RolesSeeder

> **HECHO (09/10/2026).** `app/Database/Seeds/RolesSeeder.php` (idempotente; renombra `roles.name` sin mover ids), test `tests/database/Seeds/RolesSeederTest.php`. Deploy: `php spark migrate` y después `php spark db:seed RolesSeeder`. Corrido en `turnero_db`: 2 = medical y 3 = patient corregidos, sin tocar `users.role_id`. El resto es el texto original.

Hoy los roles (admin, patient, medical, reception) no se siembran. Hay que insertarlos a mano después de migrar. Un seeder los crearía en el deploy.

- **Por qué importa:** `POST /platform/tenants` crea el primer ADMIN de un Tenant con `role_id = 1` (FK a `roles`). Sin la fila `admin` en `roles`, el alta falla y hace rollback del Tenant.
- **Ids:** deben coincidir con `app/Constants/Role.php` (ADMIN=1, MEDICAL=2, PATIENT=3, RECEPTION=4).
- **Idempotente:** que se pueda correr varias veces sin duplicar (los ids son fijos).
- **Tests:** los tests de base de datos siembran el rol que necesitan a mano, porque `FastDatabaseTestTrait` vacía `roles` entre tests. No dependen del seeder.
- **Anotado el 07/10/2026** durante el Platform Admin (Paso 5 del multitenant).

## Desajuste de ids detectado en `turnero_db` (07/10/2026)
`app/Constants/Role.php` es la fuente de verdad. El RolesSeeder tiene que insertar los roles con **estos ids**:

| id | Constante | Nombre |
|---|---|---|
| 1 | `Role::ADMIN` | admin |
| 2 | `Role::MEDICAL` | medical |
| 3 | `Role::PATIENT` | patient |
| 4 | `Role::RECEPTION` | reception |

Hoy `turnero_db` tiene **2 = patient y 3 = medical (invertidos)**. El código usa los ids y no los nombres, así que no rompe nada visible, pero cualquier lugar que muestre o consulte el nombre del rol, o un deploy que siembre desde otra fuente, daría el resultado contrario. `turnero_test` no está afectada: cada test siembra el rol que necesita con el id de la constante.

Al corregirlo:
- Primero revisar qué usuarios hay con `role_id` 2 y 3 en `turnero_db`: las filas de `users`, `medicals` y `patients` apuntan por id, así que **no se renombra a ciegas** (cambiar los nombres de los ids 2 y 3 es seguro; intercambiar los ids no, por la FK `users.role_id`). Lo más simple es renombrar `roles.name` para que coincida con la constante, sin mover ids.
- El seeder debe ser idempotente: insertar o actualizar el `name` por `id`.

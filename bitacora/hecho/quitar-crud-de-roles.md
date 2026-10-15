# Se quitó el CRUD de roles

- **Estado:** hecho (15/10/2026).
- **Por qué:** `roles` es una tabla global (sin `tenant_id`): el ADMIN de un Tenant podía crear, renombrar o borrar roles que ven todos los Tenants, y un rol creado así no tiene funciones (los permisos están por id en `Constants\Role`, `PermissionsService` y los Filters). Los roles los agrega el desarrollador.
- **Qué cambió (backend):**
  - Se borró `/roles` completo: controllers, rutas, services, DTOs, converter de response y excepciones del CRUD.
  - `Constants\Role::NAMES` es la lista única (id => nombre). `RolesSeeder` y `Role::isValid()` leen de ahí.
  - Los validators de User validan el `roleId` en memoria con `Role::isValid()`: no consultan la tabla. Se borraron `RoleFinderService`, `RoleModel`, `PrimitiveToRoleConverter` y `Entity\Role\Role`.
  - La tabla `roles` y `RolesSeeder` se mantienen (FK `users.role_id`).
- **Consecuencia a tener en cuenta:** sin el rol sembrado, el alta de un Tenant ya no falla con `RoleNotFoundException` sino con `UserCannotBeCreatedException` (500), porque el `INSERT` falla por la FK. El seeder es parte obligatoria del deploy.
- **Qué cambió (frontend):** se borró `features/roles`, la ruta y la pestaña; `ROLE_OPTIONS` en `constants/role.js` es la única lista de roles y la leen el formulario de usuarios, los filtros, las columnas y `UserProfile`.
- **Cómo agregar un rol:** ver `.claude/rules/auth-rbac.md`.

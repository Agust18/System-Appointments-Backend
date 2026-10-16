# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Comandos

```bash
# Setup inicial
docker compose up -d --build
composer install
php spark migrate

# Correr todos los tests
vendor\bin\phpunit

# Correr un archivo de test específico
vendor\bin\phpunit tests/ruta/al/FooTest.php

# Correr tests por nombre
vendor\bin\phpunit --filter testAlgo

# CLI de CodeIgniter
php spark <comando>
```

---

## Arquitectura

API REST en CodeIgniter 4 con arquitectura en capas estricta. **No se usa el ActiveRecord ni el Query Builder nativos de CI4.** Cada capa tiene una única responsabilidad; los datos fluyen en una sola dirección a través de Converters explícitos.

### Flujo de una request

```
HTTP → Filter (auth/RBAC) → Controller → Service (verbo) → Model → DB
                                               ↕                ↕
                                          [Service          [Converter]
                                          delegado]
                                               ↓
                                         Converter → Response DTO → JSON
```

### Responsabilidades por capa

| Capa | Regla |
|---|---|
| 
| **Service (verbo)** | Dueño de toda la lógica de negocio para un verbo HTTP. Delega en Validator Services y Delegate Services. Devuelve un Response DTO tipado o `void`. |
| **Service (delegado)** | Operación técnica no ligada a un verbo: booking atómico con lock (`AppointmentBookingService`), construcción de filtros con conciencia de RBAC (`AppointmentFilterBuilderService`), disponibilidad de horarios (`AppointmentAvailabilityService`). Lo llaman otros Services. |
| **Service (validador)** | Reglas de dominio sin efectos secundarios. Siempre en el subfolder `Validations/`. Lanza una excepción tipada si falla. |
| **Model** | SQL puro via `$db->query($sql, $params)` únicamente. Acepta y devuelve Entities. Después de un `INSERT`, re-fetchea con `find($this->db->insertID())` y devuelve la entidad hidratada.Si mas de una consulta repite query usar Querybuilder |
| **Entity** | Objeto de dominio mutable. Tiene factory estático `fromRequest()` y método `modify(UpdateRequest)`. Las Projection Entities (en subfolder `Projection/`) son de solo lectura y nunca se persisten. |
| **Converter** | Un único método `convert()`, sin estado. `PrimitiveToFooConverter`: `stdClass` (fila de DB) → Entity (se usa dentro de Models). `FooToFooResponseConverter`: Entity → Response DTO (se usa dentro de Services). |
| **Request DTO** | `final readonly class` con props privadas promovidas + getters. Lleva `static rules(): array` y `static messages(): array` para la validación de CI4. |
| **Response DTO** | `final readonly class` con props `public` promovidas. Se serializa directamente por `json_encode()`. |
| **Exception** | Extiende `Exception` con el código HTTP en `getCode()`. `ApiExceptionHandler` mapea `getCode()` → respuesta HTTP. `ValidationException` (422) es la única con campo `errors`. |

---

## Detalles bajo demanda (leer solo si la tarea lo requiere)
- Auth/RBAC y roles → `.claude/rules/auth-rbac.md`
- Sistema de Email → `.claude/rules/email.md`
- Booking atómico de turnos → `.claude/rules/booking-atomico.md`
- Reserva pública (`/public/*`), identidad sin cuenta, throttle → `.claude/rules/public-booking.md`
- Panel de plataforma (`/platform/*`), `platform_admins`, suspender Tenants → `.claude/rules/platform-admin.md`
- Multitenant: glosario en `bitacora/glosario.md`, decisiones en `bitacora/adr/`, plan en `bitacora/pendiente/multitenant-plan.md`.

## Multitenant (implementado)

- Toda consulta a tablas con tenant_id debe filtrar por tenant.
- Los Models ya lo hacen. Si agregás una query nueva, incluí el filtro.
- Los tests usan FastDatabaseTestTrait para no rehacer migraciones.
- No consultar tablas con `tenant_id` sin filtro (excepto en tests de aislamiento).
- `/platform/*` (SuperAdmin) **no tiene Tenant**: el filtro global `tenant` está exceptuado en `Filters.php` y se autentica con `platformAdminOnly`, que reemplaza a `auth`. Un token de usuario no entra ahí y uno de plataforma no entra a las rutas de Tenant. Las tablas `platform_admins` y `platform_admin_tokens` son globales.
- Toda ruta nueva de Tenant debe llevar `auth`; `PlatformRoutesTest` falla si queda una sin `auth` o sin `tenant`. Excepción acordada: `GET /tenant/config` (pública para el login; `cors` + `throttle:60,60`; solo devuelve `slug`, `name`, `landingEnabled` y el bloque `contact` público (teléfono, WhatsApp, email de contacto, dirección, horarios), nunca `reception_email`, `reply_to_email` ni `frontend_url`).
- Configuración por Tenant: tabla `tenant_settings` (1:1, PK `tenant_id`), `TenantSettingsModel` lee con `TenantContext`. `TenantCreatorService` crea la fila en la misma transacción del alta.


---

## Convenciones de naming (recurso: `Foo`)

### Controllers — `app/Controllers/Foo/`

| Clase | Método | Ruta |
|---|---|---|
| `FooPostController` | `create()` | `POST /foos` |
| `FooGetController` | `find(int $id)` | `GET /foos/{id}` |
| `FoosGetController` | `search()` | `GET /foos` |
| `FooPutController` | `do(int $id)` | `PUT /foos/{id}` |
| `FooDeleteController` | `do(int $id)` | `DELETE /foos/{id}` |

No existe PATCH. Todos los controllers extienden `ResourceController`.

### Services — `app/Services/Foo/`

| Clase | Llamado desde | Devuelve |
|---|---|---|
| `FooCreatorService` | `FooPostController` | `FooResponse` |
| `FooUpdaterService` | `FooPutController` | `FooResponse` |
| `FooDeleterService` | `FooDeleteController` | `void` |
| `FooFinderService` | `FooGetController` | `Foo` (entity) via `find()` / `FooResponse` via `findResponse()` |
| `FoosSearcherService` | `FoosGetController` | `array{data: FooResponse[], total: int}` |
| `Validations/FooCreateValidatorService` | `FooCreatorService` | `void` |
| `Projection/FoosProjectionSearcherService` | `FoosGetController` | `array{data: FooProjectionResponse[], total: int}` |

`FinderService` siempre expone dos métodos: `find(int $id): Foo` para uso interno entre Services, y `findResponse(int $id): FooResponse` para el Controller.

### Checklist de archivos para un nuevo recurso CRUD

1. `app/Entity/Foo/Foo.php`
2. `app/Dto/Request/Foo/FooRequest.php` — con `rules()` + `messages()`
3. `app/Dto/Request/Foo/FooUpdateRequest.php` — campos nullable
4. `app/Dto/Request/Foo/FooFilterRequest.php` — paginación + filtros
5. `app/Dto/Response/Foo/FooResponse.php`
6. `app/Converter/Foo/PrimitiveToFooConverter.php`
7. `app/Converter/Foo/FooToFooResponseConverter.php`
8. `app/Models/Foo/FooModel.php`
9. `app/Exception/Foo/FooNotFoundException.php`
10. `app/Services/Foo/Validations/FooCreateValidatorService.php`
11. `app/Services/Foo/FooCreatorService.php`
12. `app/Services/Foo/FooFinderService.php`
13. `app/Services/Foo/Validations/FooUpdateValidatorService.php`
14. `app/Services/Foo/FooUpdaterService.php`
15. `app/Services/Foo/Validations/FooDeleteValidatorService.php`
16. `app/Services/Foo/FooDeleterService.php`
17. `app/Services/Foo/FoosSearcherService.php` (si hay listado)
18. `app/Controllers/Foo/FooPostController.php`
19. `app/Controllers/Foo/FooGetController.php`
20. `app/Controllers/Foo/FoosGetController.php`
21. `app/Controllers/Foo/FooPutController.php`
22. `app/Controllers/Foo/FooDeleteController.php`
23. `app/Config/Routes.php` — agregar las 5 rutas

Para listados con JOIN, agregar también después del paso 7:
- `app/Entity/Foo/Projection/FooProjection.php`
- `app/Dto/Response/Foo/Projection/FooProjectionResponse.php`
- `app/Converter/Foo/Projection/FooProjectionToFooProjectionResponseConverter.php`
- `app/Services/Foo/Projection/FoosProjectionSearcherService.php`

---
---
## Tests

- **NUNCA correr la suite completa sin pedirlo explícitamente.** Tarda ~44 min.
- Para auditar o leer código: NO correr tests. Solo grep, cat, y lectura.
- Para verificar un cambio: correr solo los tests del módulo afectado:
  `vendor/bin/phpunit --filter NombreDelTest`
- Antes de commit: correr la suite completa solo si el usuario lo pide.
- Siempre dentro del contenedor: `docker exec turnero_app sh -c "cd /var/www/html && vendor/bin/phpunit ..."`
- El host tiene PHP 8.1 y falla. Los tests van en el contenedor.
---

## Prohibiciones

- **Nunca usar Query Builder.** Todo SQL es un string puro pasado a `$db->query($sql, $params)`.
- **Nunca extender `CI4\Model`.** Los Models son clases planas con `BaseConnection $db`.
- **Nunca poner lógica de negocio en Controllers.** Solo: parsear HTTP → DTO → llamar Service → devolver JSON.
- **Nunca construir un Response DTO directamente** en un Service o Model. Siempre usar un Converter.
  - **Excepción: Metrics** (`app/Services/Metrics/`). Son agregaciones, no entidades: no usan Projection, Converter ni Response DTO.
    Los Services castean `(int)`/`(float)` y devuelven arrays en camelCase; todo el SQL vive en `MetricsModel`.
- **Nunca persistir una Projection Entity.** Las entidades en `Entity/*/Projection/` son de solo lectura.
- **Nunca usar contenedor de DI.** Las dependencias se instancian con `new` en los constructores.
- **Nunca usar PATCH.** Las actualizaciones usan PUT únicamente.
- **Los códigos de excepción deben ser HTTP válidos** (4xx/5xx). `ApiExceptionHandler` usa `$exception->getCode()` como status HTTP.

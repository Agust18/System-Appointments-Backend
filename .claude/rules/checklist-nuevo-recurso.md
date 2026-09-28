# Checklist para crear un recurso CRUD nuevo

Reemplazar `Foo` por el nombre del recurso real (ej: `Appointment`, `User`).

## Archivos a crear (en orden)

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

## Si el listado lleva JOIN

Agregar después del paso 7:
- `app/Entity/Foo/Projection/FooProjection.php`
- `app/Dto/Response/Foo/Projection/FooProjectionResponse.php`
- `app/Converter/Foo/Projection/FooProjectionToFooProjectionResponseConverter.php`
- `app/Services/Foo/Projection/FoosProjectionSearcherService.php`

## Detalle de Services y Controllers

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
| `FooFinderService` | `FooGetController` | `Foo` via `find()` / `FooResponse` via `findResponse()` |
| `FoosSearcherService` | `FoosGetController` | `array{data: FooResponse[], total: int}` |
| `Validations/FooCreateValidatorService` | `FooCreatorService` | `void` |
| `Projection/FoosProjectionSearcherService` | `FoosGetController` | `array{data: FooProjectionResponse[], total: int}` |

`FinderService` siempre expone dos métodos: `find(int $id): Foo` para uso interno entre Services, y `findResponse(int $id): FooResponse` para el Controller.
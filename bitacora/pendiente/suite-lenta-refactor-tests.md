# Suite lenta (~25 min): regla de trabajo y refactor de tests

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** 4-6 h (refactor futuro)
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🟡 Suite lenta (~25 min)

## Detalle

Cada test de DB con `$refresh = true` re-migra todo `turnero_test` (unos 12 s por test). Correr con `docker exec turnero_app php vendor/bin/phpunit` (el host `db` solo resuelve dentro de Docker). `turnero_test` se reseteó el 30/09/2026 porque tenía `migrations` marcada sin las tablas.

**Regla de trabajo** (mientras tanto):
- Después de cada cambio: tests del archivo afectado.
- Después de cada commit: tests de la fase.
- Al final de la fase: suite completa.
- **No** correr la suite completa después de cada cambio.
- **Excepción:** si el cambio toca algo compartido (Service base, Converter, Model), correr la suite completa.

**Refactor futuro:** tests con transacciones que se revierten en vez de re-migrar (~4-6 h).

⚠️ Tests que rompen el `refresh` si no se cuidan (se descubrió en las fases 1-3):
- El `down()` de `ReplaceUsernameWithFirstLastName` reconstruye `users.username` (UNIQUE) desde nombre + apellido: los tests deben sembrar usuarios con **nombres únicos** (`uniqid()`).
- Los tests que dejan un índice borrado o filas que impidan revertir una migración (`uq_users_dni`; `uq_patients_user_id` ya no existe: `patients.user_id` se soltó en la fase 1 de paciente-sin-usuario) tienen que restaurarlo en `tearDown`.

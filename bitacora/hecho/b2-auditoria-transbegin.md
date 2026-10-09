# B2 — Auditoría de transStart() vs transBegin()

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › ✅ Cerrado › Booking / transacciones (30/09/2026)

## Detalle

- ✅ **B2 — Auditoría `transStart()` vs `transBegin()`**: todo `app/` usa `transBegin` + `transCommit` + `transRollback` manual (correcto: el rollback depende de excepciones de dominio, no de `transStatus`). Única inconsistencia: los 2 booking services capturaban `Exception` en vez de `\Throwable`; unificado (commit `8671dcb`). `MedicalCreatorService` y los seeders siguen el mismo patrón por grep, sin revisión línea a línea.

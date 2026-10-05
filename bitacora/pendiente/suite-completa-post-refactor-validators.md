# Suite completa sin correr tras el refactor de validators

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** ~44 min de corrida
- **Fecha:** 03/10/2026
- **Origen:** Refactor de validators de Appointment

## Detalle

- No se corrió la suite completa después del refactor. Solo se verificó `tests/database/Appointment` (84 tests en verde).
- `tests/database` estaba roto por la migración de `InitialSchema`: la base `turnero_test` había quedado a medias de una corrida interrumpida. Se recreó la base.
- Conviene correr la suite completa cuando haya tiempo, para verificar que no se rompió nada fuera de `tests/database/Appointment`.
- Relacionado: [Suite lenta](suite-lenta-refactor-tests.md).

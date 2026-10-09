# Bug "estado inválido" al activar/desactivar

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › ✅ Cerrado › Bugs y fixes

## Detalle

- **Bug "estado inválido" al activar/desactivar** (30/09/2026): CI4 StrictRules `in_list` rechaza `bool` JSON nativo. Fix centralizado en `RequestValidator`: convierte bool de primer nivel del body a 0/1 antes de validar. Cubre MedicalSchedule, MedicalService, Medical, Service y User. Reglas actualizadas a `in_list[0,1,true,false]`.
  - ⚠️ **Nota para el futuro:** CI4 NO tiene regla `boolean`. No usarla. Para campos bool, convertir a 0/1 antes de validar.

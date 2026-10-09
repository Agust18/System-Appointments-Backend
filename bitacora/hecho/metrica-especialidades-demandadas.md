# Métrica: especialidades más demandadas

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › 📊 Métricas del dashboard (backend)

## Detalle

- ✅ **Especialidades más demandadas** — cerrada (30/09/2026). `GET /metrics/top-specialties`: top 5 por especialidad del médico (`appointments → medicals.specialty_id`; `services` no tiene `specialty_id`, no se tocó el schema). Excluye `cancelled`. El front oculta el widget si hay filtro de médico y no envía `medicalId`.

# Refactor de métricas (sin Projection ni Converter)

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 30/09/2026
- **Origen:** PENDIENTES-BACKEND.md › 📊 Métricas del dashboard (backend)

## Detalle

- ✅ **Refactor métricas** — cerrado (30/09/2026). Métricas no usa Projection+Converter. Es intencional: son agregaciones, no entidades. 1 método en `MetricsModel` + 1 Service (devuelve array) + 1 Controller por métrica; JSON verificado idéntico con diff antes/después. Excepción documentada en `CLAUDE.md`.

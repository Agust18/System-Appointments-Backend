# Evoluciones: ver el registro de accesos (solo admin)

- **Estado:** ⏳ Pendiente
- **Repo:** backend + frontend
- **Esfuerzo:** mediano
- **Fecha:** —
- **Origen:** revisión de evoluciones (2026-10-21)

## Contexto

`EvolutionAccessLoggerService` ya guarda cada lectura en `evolution_access_log` (`user_id`, `patient_id`, `evolution_id` NULL = listado, `action`, `created_at`). No existe ningún endpoint ni pantalla para consultarlo: hoy solo se ve con SQL.

## Alcance acordado

- Solo **quién** accedió (usuario), a qué paciente, qué acción y cuándo. **No se guarda la IP** (decisión del dueño: no hace falta); la tabla no cambia.
- Solo ADMIN. Médico y recepción: `403`.

## Detalle

- [ ] Backend: `GET` con `role:admin` y su Searcher (filtros: paciente, usuario, rango de fechas, paginación).
- [ ] Backend: Model con SQL puro (JOIN `users` y `patients` para mostrar nombres, siempre con `tenant_id`), Projection + Response DTO + Converters.
- [ ] Backend: test crítico: médico y recepción reciben `403`; no se ve el log de otro Tenant.
- [ ] Frontend: servicio, hook, página con tabla y filtros, ruta y entrada de menú visibles solo para admin.
- [ ] Documentar en `.claude/rules/evolutions.md` y en el contrato (`bitacora/hecho/contrato-evoluciones.md`).

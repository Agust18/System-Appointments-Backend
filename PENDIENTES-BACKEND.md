# PENDIENTES — BACKEND

Última actualización: 30/09/2026

---

## 🔴 Bugs reales

- **Turno de prueba 110 en `turnero_db`** (médico 10, servicio 3, 2027-03-02 11:00, nota "TEST-R6 editado"): quedó sin borrar porque DELETE fallaba (ya arreglado). **Lo borra el usuario a mano.**

---

## ✅ Cerrado (no volver a tocar)

### Booking / transacciones (30/09/2026)
- ✅ **B2 — Auditoría `transStart()` vs `transBegin()`**: todo `app/` usa `transBegin` + `transCommit` + `transRollback` manual (correcto: el rollback depende de excepciones de dominio, no de `transStatus`). Única inconsistencia: los 2 booking services capturaban `Exception` en vez de `\Throwable`; unificado (commit `8671dcb`). `MedicalCreatorService` y los seeders siguen el mismo patrón por grep, sin revisión línea a línea.
- ✅ **R6 — Booking atómico extraído**: nuevo Service delegado `AppointmentSlotLockService::run()` (lock `GET_LOCK` + transacción + validación de servicio + bloque + solapamiento + persistencia inyectada). `AppointmentBookingService` y `AppointmentUpdateBookingService` quedan como adaptadores finos. 22 tests de caracterización escritos antes del refactor (`tests/database/Appointment/`), idénticos antes y después. Suite: 237 tests OK. Endpoints verificados a mano: POST 200, PUT 200, solapamiento 409.

### Fixes posteriores a R6 (30/09/2026)
- ✅ **`DELETE /appointments/{id}` devolvía 500**: `AppointmentDeleterService` no inicializaba `$appointmentModel`. Fix de 1 línea + `AppointmentDeleterServiceTest` (borrado, no afecta otros turnos, pasado 403, inexistente 404). Commit `478d32e`.
- ✅ **`AppointmentServiceRequiredException` era código muerto**: el converter castea `service_id` NULL a 0. Guard ahora `=== null || <= 0`; turno legacy sin servicio devuelve 422 (antes 404 engañoso). El front ya lo maneja genéricamente (`parseApiError` → toast con `data.error`). Commit `648f8ed`.
  - Nota: la mitad `=== null` del guard es inalcanzable por tipos (`?int ?? int`); se dejó por especificación. Se puede simplificar a `<= 0`.
  - Nota: el mensaje de la excepción dice "debe indicar serviceId", técnico para un toast; evaluar reescribirlo ("seleccioná un servicio").

### Bugs y fixes
- **Bug "estado inválido" al activar/desactivar** (30/09/2026): CI4 StrictRules `in_list` rechaza `bool` JSON nativo. Fix centralizado en `RequestValidator`: convierte bool de primer nivel del body a 0/1 antes de validar. Cubre MedicalSchedule, MedicalService, Medical, Service y User. Reglas actualizadas a `in_list[0,1,true,false]`.
  - ⚠️ **Nota para el futuro:** CI4 NO tiene regla `boolean`. No usarla. Para campos bool, convertir a 0/1 antes de validar.
- **`crudBase`** en `UseMedicalSchedulePage.js:49` (30/09/2026) — *nota: es frontend, ver PENDIENTES-FRONTEND.md*.
- **L7 y L8** en `AppointmentPage.jsx` (total, loadingPatients) — *frontend*.
- **Bug "estado inválido"** ya está cerrado. No volver.

> ⚠️ **Ojo:** en el `PENDIENTES.md` original, varios items marcados como "cerrados" son de frontend (crudBase, L7/L8, LoginModal). Los moví al archivo de frontend. Acá solo van los de backend.

---

## 🟡 Backend: fixes defensivos

| # | Fix | Esfuerzo |
|---|---|---|
| B1 | Chequeo del INSERT en todos los Models (Patient, Medical, Appointment, etc.) | 4-6 h |
| B3 | Mover transacciones a `Operation/` (Service delegado) | 2-3 h |

---

## 🟡 Refactors backend

| # | Refactor | Dónde | Esfuerzo | Impacto |
|---|---|---|---|---|
| R5 | Extraer `isDayOff()` a `MedicalDaysOffService` | `AppointmentAvailabilityBatchService` | 30 min | Bajo |

> **R5 — Detalle:** extraer `isDayOff()` de `AppointmentAvailabilityBatchService` a `MedicalDaysOffService`, inyectarlo en batch, single-date y Booking, e implementar la query real contra `medical_days_off`. Se hace **cuando se implemente la feature de días no laborables** (hoy `isDayOff()` devuelve `false`).

---

## 📊 Métricas del dashboard (backend)

- ✅ **Tasa de ausentismo** — cerrada (30/09/2026). `noshowRate` y `cancellationRate` en `GET /metrics/kpis`.
- ✅ **Especialidades más demandadas** — cerrada (30/09/2026). `GET /metrics/top-specialties`: top 5 por especialidad del médico (`appointments → medicals.specialty_id`; `services` no tiene `specialty_id`, no se tocó el schema). Excluye `cancelled`. El front oculta el widget si hay filtro de médico y no envía `medicalId`.
- ✅ **Turnos recientes** — cerrada (30/09/2026). `GET /metrics/recent-appointments`: últimos 10 por `updated_at DESC`, filtrados por `A.fecha` (rango + `medicalId`).
- ✅ **Refactor métricas** — cerrado (30/09/2026). Métricas no usa Projection+Converter. Es intencional: son agregaciones, no entidades. 1 método en `MetricsModel` + 1 Service (devuelve array) + 1 Controller por métrica; JSON verificado idéntico con diff antes/después. Excepción documentada en `CLAUDE.md`.
- ⏳ Alertas del sistema (post-MVP).

---

## 🟡 Deuda técnica backend

- **Doble `isActive` (users + medicals)**: un médico tiene `isActive` como usuario (puede loguearse) y como médico (puede atender). Revisar si la lógica de negocio es coherente en los 4 cruces posibles:
  - user activo + medical activo → normal
  - user activo + medical inactivo → puede loguearse pero no debería aparecer para asignar turnos
  - user inactivo + medical activo → no puede loguearse pero aparece en listas
  - user inactivo + medical inactivo → dado de baja total
  Confirmar que los listados y validaciones respetan esto.

- **Suite lenta (~12-14 min)**: cada test de DB con `$refresh = true` re-migra todo `turnero_test`. Correr con `docker exec turnero_app php vendor/bin/phpunit` (el host `db` solo resuelve dentro de Docker). `turnero_test` se reseteó el 30/09/2026 porque tenía `migrations` marcada sin las tablas.

- **Migración al RequestValidator**: unificar la validación de requests que hoy está dispersa.

---

## 🟢 Consistencia de código (backend)

- [ ] Refactor R5 (R6 ✅)
- [ ] Fixes defensivos B1, B3 (B2 ✅)
- [ ] Migración al RequestValidator

---

## Prioridad sugerida (backend)

1. **R5** — Extraer `isDayOff()` (30 min, preparación para días no laborables).
2. **B3** — Mover transacciones a `Operation/` (2-3 h). Con R6 hecho, parte ya vive en `AppointmentSlotLockService`.
3. **B1** — Chequeo del INSERT (4-6 h, cuando escales).

---

## Cómo usar este archivo

- Este archivo es **solo para el repo del backend**.
- Cuando retomes un item, copialo y pegalo en Claude Code para armar el prompt de arranque.
- Actualizá el archivo cuando cierres items.
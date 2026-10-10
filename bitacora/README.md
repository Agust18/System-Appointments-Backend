# Bitácora — BACKEND

Última actualización: 03/10/2026

Solo tareas del **repo backend**. Los archivos `hecho/contrato-*` documentan el contrato de la API pública que el backend le deja al frontend.

**Bugs reales:** 1 — [el PUT de turnos no reusa AppointmentSlotValidatorService](pendiente/put-appointment-no-reusa-slot-validator.md)

Fuente de verdad del flujo público en el backend: `.claude/rules/public-booking.md`.

Cambios de contrato del backend que el frontend tiene que tolerar o aprovechar.

⚠️ Los endpoints `/public/*` **NO deben exponerse a internet** hasta cerrar todos los `pendiente/deploy-*` (checklist de la reserva pública).

## ✅ Hecho (21)

- [Multitenant, Grupo 3: plan detallado (`appointments`, `user_verification_tokens`)](hecho/multitenant-grupo-3-plan.md)
- [FRONTEND_URL por Tenant en emails de verificación y reset (resuelto en el Paso 4.2)](hecho/frontend-url-por-tenant.md)
- [Auditar el doble isActive: se eliminó `medicals.is_active`](hecho/auditar-doble-isactive.md)
- [B2 — Auditoría de transStart() vs transBegin()](hecho/b2-auditoria-transbegin.md)
- [Excepciones tipadas en AppointmentUpdateValidatorService](hecho/update-validator-excepciones-tipadas.md)
- [R6 — Booking atómico extraído a AppointmentSlotLockService](hecho/r6-booking-atomico-extraido.md)
- [DELETE /appointments/{id} devolvía 500](hecho/fix-delete-appointments-500.md)
- [AppointmentServiceRequiredException era código muerto](hecho/fix-service-required-exception-codigo-muerto.md)
- [Mensaje de ServiceRequired más claro](hecho/mensaje-service-required-mas-claro.md)
- [Bug "estado inválido" al activar/desactivar](hecho/bug-estado-invalido-activar-desactivar.md)
- [Métrica: tasa de ausentismo](hecho/metrica-tasa-ausentismo.md)
- [Métrica: especialidades más demandadas](hecho/metrica-especialidades-demandadas.md)
- [Métrica: turnos recientes](hecho/metrica-turnos-recientes.md)
- [Refactor de métricas (sin Projection ni Converter)](hecho/refactor-metricas.md)
- [Suite completa verificada tras la sub-fase 3.3](hecho/suite-verde-sub-fase-3-3.md)
- [Suite completa en verde tras la Fase 5](hecho/suite-verde-fase-5.md)
- [Reserva pública: cómo limita (throttle por IP y bloqueo por DNI, Fase 4)](hecho/reserva-publica-como-limita-fase-4.md)
- [Contrato: POST /patients/register (202) y UserResponse.hasAccount](hecho/contrato-patients-register-202-y-hasaccount.md)
- [Contrato: wizard público (sin login) y manejo de errores](hecho/contrato-wizard-publico.md)
- [Contrato: API pública de reserva (Fase 3)](hecho/contrato-api-publica-fase-3.md)
- [Contrato: se eliminó slotStepMinutes de /services y /medicals/{id}/services](hecho/contrato-eliminar-slot-step-minutes.md)
- [Contrato: cambios de las Fases 1-2 (normalización de DNI, teléfono y email)](hecho/contrato-cambios-fases-1-2-normalizacion.md)

## ⏳ Pendiente (33)

- [Suite rápida: pendientes tras `FastDatabaseTestTrait` (truncar solo tablas con filas; mutación en `MedicalServiceModel`)](pendiente/suite-rapida-pendientes.md)
- [DashboardDemoSeeder: no siembra `medical_schedules` y oculta fallos de INSERT](pendiente/seeder-dashboard-bugs.md)
- [Auditar el modelo de borrado: lógico (`deleted_at`) vs físico (post-multitenant)](pendiente/borrado-logico-vs-fisico.md)
- [Conteo y estadísticas de turnos no hacen JOIN a `services` (total ≠ lista)](pendiente/count-y-stats-de-turnos-sin-join-a-services.md)
- [📌 Multitenant: lo que falta (módulos, tope de turnos, timezone/logo, fallback `demo`)](pendiente/multitenant-plan.md) — decisiones en [glosario](glosario.md) y [ADR](adr/)
- [Fallback `demo` del filtro tenant con más de un Tenant](pendiente/fallback-demo-con-mas-de-un-tenant.md)
- [B1 — Chequeo del INSERT en todos los Models](pendiente/b1-chequeo-insert-models.md)
- [B3 — Mover transacciones a Operation/ (Service delegado)](pendiente/b3-transacciones-a-operation.md)
- [R5 — Extraer isDayOff() a MedicalDaysOffService](pendiente/r5-extraer-isdayoff.md)
- [Migración al RequestValidator](pendiente/migracion-al-request-validator.md)
- [Micro-deuda: guard de AppointmentUpdateBookingService](pendiente/micro-deuda-guard-update-booking.md)
- [Alertas del sistema (post-MVP)](pendiente/alertas-del-sistema.md)
- [Deploy: app.trustedProxies](pendiente/deploy-trusted-proxies.md)
- [Deploy: writable/cache escribible](pendiente/deploy-cache-escribible.md)
- [Deploy: cache compartido (Redis) con varias instancias](pendiente/deploy-cache-compartido-redis.md)
- [Deploy: cors.allowedOrigins / allowedOriginsPatterns](pendiente/deploy-cors-origenes.md)
- [Deploy: migraciones probadas en una copia de la base](pendiente/deploy-migraciones-en-copia-de-base.md)
- [Deploy: EMAIL_* configurado](pendiente/deploy-email-config.md)
- [Deploy: reception.email configurado](pendiente/deploy-reception-email.md)
- [CORS en rutas privadas (no incluido)](pendiente/cors-en-rutas-privadas.md)
- [Flujo autenticado: fecha/hora sin largo exacto](pendiente/fecha-hora-exact-length-flujo-autenticado.md)
- [Tope de turnos futuros "blando"](pendiente/tope-turnos-futuros-blando.md)
- [CORS: exponer Retry-After](pendiente/cors-exponer-retry-after.md)
- [Agregar campo code a los 409 de POST /public/appointments](pendiente/campo-code-en-409-publicos.md)
- [Caso familiar (padre/hijo)](pendiente/caso-familiar-padre-hijo.md)
- [Suite lenta: regla de trabajo y refactor de tests (resuelto en gran parte con `FastDatabaseTestTrait`, ver suite-rapida-pendientes)](pendiente/suite-lenta-refactor-tests.md)
- [Gap funcional: el PUT de turnos no reusa AppointmentSlotValidatorService](pendiente/put-appointment-no-reusa-slot-validator.md)
- [AppointmentOverlapValidatorService](pendiente/appointment-overlap-validator.md)
- [Duplicaciones menores en los Services de Appointment](pendiente/appointment-duplicaciones-menores.md)
- [Suite completa sin correr tras el refactor de validators](pendiente/suite-completa-post-refactor-validators.md)
- [Commit pendiente: refactor de validators de Appointment](pendiente/commit-refactor-validators-appointment.md)
- [Tests de CORS dependen del .env local](pendiente/tests-cors-dependen-del-env-local.md)

## 🔵 Features (2)

- [WhatsApp como canal de aviso alternativo al email](features/whatsapp-canal-de-aviso.md)
- [Perfiles de familia](features/perfiles-de-familia.md)

## Prioridad sugerida

1. **R5** — Extraer `isDayOff()` (30 min, preparación para días no laborables).
2. **B3** — Mover transacciones a `Operation/` (2-3 h). Con R6 hecho, parte ya vive en `AppointmentSlotLockService`.
3. **B1** — Chequeo del INSERT (4-6 h, cuando escales).

## Cómo usar

- Esta bitácora es **solo para el repo del backend**.
- Un archivo por tarea, en kebab-case, con Estado, Repo, Esfuerzo, Fecha, Origen y Detalle.
- Cuando retomes un item, copialo y pegalo en Claude Code para armar el prompt de arranque.
- Cuando cierres un item, movelo a `hecho/` y actualizá este índice.

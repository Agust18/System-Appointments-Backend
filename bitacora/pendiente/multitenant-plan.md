# Multitenant: lo que falta

- **Actualizado:** 09/10/2026
- **Estado:** el aislamiento por Tenant, los prefijos de clave, `tenant_settings` y el panel de plataforma están hechos. Quedan 4 cosas.
- **Decisiones firmes:** `glosario.md` y `adr/001` a `004`. **Historial largo** (pasos, regresiones, lecciones): `git log -- bitacora/pendiente/multitenant-plan.md`.
- **Cómo funciona hoy:** reglas de Tenant en `CLAUDE.md`, panel en `.claude/rules/platform-admin.md`, emails en `.claude/rules/email.md`.

## Pendiente

1. **Módulos por Tenant.** Tabla `tenant_modules`, `app/Constants/TenantModule.php` (`public_booking`, `metrics`, `email_notifications`), filtro `module:{nombre}` (403 si está apagado) y rutas en `/platform/tenants/{id}/modules`. Sin código todavía.
2. **Tope de turnos configurable.** `max_active_future_appointments` en `tenant_settings`; hoy es la constante `MAX_ACTIVE_FUTURE_APPOINTMENTS = 3` en `PublicAppointmentCreatorService`.
3. **`timezone` y logo por Tenant.** `timezone` en `tenant_settings`. El logo requiere subida de archivos: ver [logo-por-tenant.md](logo-por-tenant.md).
4. **Sacar el fallback `demo`.** `tenant.fallbackSlug` en `TenantResolver`, `.env.example` y `phpunit.xml.dist`. Ver [fallback-demo-con-mas-de-un-tenant.md](fallback-demo-con-mas-de-un-tenant.md).

## Descartado (verificado el 09/10/2026)

- **Comparar el Tenant del usuario con el del header en `Auth`:** ya lo cubre `UserModel::findByToken` (`AND tenant_id = ?`). Un token de otro Tenant da 401; protegido por `AuthTest::testValidTokenOfAnotherTenantReturns401`.
- **Test integrador de ids de otro Tenant en el body:** los Models scopeados devuelven `null` y tienen sus tests de aislamiento. Sin valor adicional.

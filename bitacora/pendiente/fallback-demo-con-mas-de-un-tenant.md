# Fallback `demo` del filtro `tenant`: ¿sigue siendo útil con más de un Tenant?

- **Estado:** pendiente
- **Repo:** backend (y frontend, si se decide forzar el header)
- **Esfuerzo:** bajo
- **Fecha:** 06/10/2026
- **Origen:** decisión de transición del Paso 2 del multitenant

## Detalle
El filtro `tenant` usa `tenant.fallbackSlug` (`.env`, valor `demo`) cuando falta `X-Tenant` y el entorno no es producción. Con un solo Tenant en desarrollo es cómodo, pero cuando haya más de uno puede ocultar errores: una request sin header cae silenciosamente en `demo` y un bug del frontend que olvide mandar `X-Tenant` no se ve en desarrollo, solo en producción (donde responde 400).

**Cuando haya más de un Tenant, revisar** si el fallback sigue siendo útil en desarrollo o si hay que forzar `X-Tenant` también ahí (borrando `tenant.fallbackSlug` del `.env`, del `.env.example` y de `phpunit.xml.dist`, y el método `fallbackSlug()` de `TenantResolver`).

Relacionado: `multitenant-plan.md` (la eliminación del fallback ya figura como paso de cierre del Paso 2).

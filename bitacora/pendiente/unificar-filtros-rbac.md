# Unificar los filtros de RBAC

- **Estado:** ⏳ Pendiente (hacer **después** de la fase 2 de `paciente-sin-usuario.md`)
- **Repo:** backend
- **Esfuerzo:** ~1–2 días
- **Fecha:** 2026-10-20
- **Origen:** comparación con `Sysacad/sysacad-backend`, que tiene las rutas más limpias (un solo filtro `auth` y el rol chequeado dentro de cada Service).

## Por qué esperar a la fase 2
Sacar el rol PATIENT elimina ramas de `appointmentPermission`, `appointmentCreatePermission`, `appointmentViewPermission`, `medicalViewPermission`, `patientViewPermission` y `PermissionsService`. Refactorizar antes es trabajar dos veces sobre lo mismo.

## Qué sí hacer
- [ ] Un filtro `role` con argumentos (`role:admin,reception`, `role:admin,reception,medical`) que reemplace a `adminOnly`, `adminOrReception` y `adminReceptionOrMedical`. Sigue declarándose en la ruta (el permiso se ve en `Routes.php`). Devuelve `Response` 403, nunca lanza excepción.
- [ ] Una sola fuente para la propiedad de datos (qué turno/médico/paciente puede ver o editar cada rol): `PermissionsService`. Hoy se reparte entre filtros, validators y builders (`session('roleId')` leído en 5 lugares).
- [ ] Evitar la doble carga: hoy el filtro busca el turno para decidir y el Service lo vuelve a buscar.
- [ ] Mantener `PlatformRoutesTest` (matriz de filtros por ruta) y extenderlo para verificar el rol exigido por cada ruta de Tenant: es lo que le falta a Sysacad.

## Qué NO copiar de Sysacad
- Mover todo el RBAC a los Services: pierde la visibilidad en las rutas y un `assert` olvidado deja el endpoint abierto sin que nada avise. En Clinica hay además rutas públicas, de plataforma y multitenant que deben seguir resueltas por filtros (`tenant`, `cors`, `throttle`, `platformAdminOnly`).
- El usuario autenticado en estado estático global (`AuthenticatedUserService`): incómodo de testear y problemático con workers persistentes.
- Que el filtro `Auth` lance una excepción al faltar el token: acá los filtros cortan con `Response`.

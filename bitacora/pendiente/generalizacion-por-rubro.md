# Generalizar el sistema a otros rubros (más allá de salud)

- **Estado:** pendiente. El MVP se cierra enfocado en salud; esto se va haciendo de a poco, empezando cuando aparezca el primer cliente de otro rubro.
- **Idea:** el panel de plataforma elige el **rubro** del Tenant y el frontend muestra el vocabulario que corresponde (paciente / cliente / mascota; médico / profesional / peluquero), y se activan solo los módulos de ese rubro. El cliente no edita nada de esto (igual que el resto de la configuración).

## Piezas, en el orden en que conviene hacerlas

1. **Un único diccionario de textos en el frontend** (`terminology.js` + hook `useTerminology()`), con los valores de salud por defecto. Hoy "Paciente / Médico / Especialidad" está escrito a mano en ~96 archivos (~324 ocurrencias). Los textos nuevos ya deberían usarlo; los existentes se migran al tocar cada archivo. `ROLE_OPTIONS` (`constants/role.js`) es el primero que debería leer de ahí.
   - Guardar singular y plural completos por término; en español armar "Nuevo " + palabra rompe el género.
2. **Campos obligatorios de salud en el esquema**: `medicals.tuition NOT NULL` (matrícula), `patients.birth_date NOT NULL` y `medicals.specialty_id NOT NULL` con FK. Es lo que realmente impide usar el sistema en otro rubro; decidir cuáles pasan a opcionales con los requisitos del primer cliente real a la vista.
3. **Rubro por Tenant**: columna `vertical` (preset en código: `health`, `generic`, ...) + override opcional del vocabulario en `tenant_settings`, editable con `PUT /platform/tenants/{id}/settings`. Los textos de los emails (`app/Email/Appointment/*`, ~20 ocurrencias) deben leer el mismo vocabulario (resolver análogo a `TenantEmailSettingsResolverService`).
4. **Exponer el vocabulario antes del login** (reserva pública y pantalla de login): hay que agregarlo a `GET /tenant/config`, hoy acordado para devolver solo `slug` y `name`. Las etiquetas no son datos sensibles, pero implica cambiar ese acuerdo y su documentación.
5. **Bloqueo de módulos según el rubro** (por ejemplo, Especialidades solo en salud). Ver el punto de módulos en `multitenant-plan.md`.

## Decisiones tomadas / descartadas

- **No renombrar tablas ni rutas** (`medicals`, `patients`, `/medicals`, `/patients`, `specialties`): el usuario final no ve nombres de API y el costo es alto. Lo que cambia es el vocabulario en pantalla.
- **Renombrar `Role::MEDICAL` (y `ROLES.*` del frontend) a `PROFESSIONAL`** (`Role::PATIENT` ya no existe: el paciente dejó de ser usuario, ver `hecho/paciente-sin-usuario.md`; el cliente final es una ficha en `patients`): solo cosmético (163 ocurrencias en 44 archivos del backend, casi todas en tests, y 14 en 7 archivos del frontend; los ids 2 y 3 no cambian). Hacerlo con buscar y reemplazar únicamente cuando haya un motivo real, junto con el punto 1.
- **Roles por Tenant**: la tabla `roles` es global y todos los Tenants comparten los mismos cuatro. Si un rubro llegara a necesitar un rol propio que otro no debe ver, haría falta `tenant_id` en `roles`. Hoy no hace falta.

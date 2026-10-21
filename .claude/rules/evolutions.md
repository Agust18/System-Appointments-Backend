# Evoluciones de consulta

Lo que el médico registra de una atención. Son **datos de salud**: se tratan con más restricciones que el resto. Contrato para el frontend: `bitacora/hecho/contrato-evoluciones.md`. Plan y decisiones: `bitacora/features/evoluciones-de-consulta.md` (raíz del monorepo).

Código: `app/Controllers/Evolution/`, `app/Services/Evolution/` (+ `Validations/`), `app/Models/Evolution/`, `app/Entity/Evolution/`. Tablas `evolutions` y `evolution_access_log` (ambas con `tenant_id`).

**Nombre:** se llama "evolución" en código, rutas y UI. No usar "historial/historia clínica" (decisión del dueño del producto) ni "consulta" a secas (`/consulta` es la pantalla pública del turno).

## Reglas que no se rompen

- **Quién escribe:** solo el médico dueño del turno (`PermissionsService::canWriteEvolution()`), con el turno `confirmed` o `completed`. `medical_id` sale de la sesión, nunca del body. Ni admin ni recepción escriben.
- **Quién lee:** el médico **solo lo suyo** (`EvolutionScopeResolverService` devuelve su `medicalId` y el Model filtra por él); el admin todo en solo lectura; recepción `403`. Ver un paciente (`patientViewPermission`, `hasRelationWithPatient`) **no** da acceso a sus evoluciones: no reutilizarlos. Con la regla "tener un turno con el paciente", un turno falso de la reserva pública abriría el acceso; por eso se filtra por autor.
- **Una evolución de otro paciente o de otro médico responde `404`**, no `403`, para no confirmar que el id existe.
- **Borrador y firma:** `signed_at` NULL = borrador editable por su autor. `EvolutionSignerService` firma y pasa el turno a `completed` en **una transacción**. No es el único camino a `completed`: el PUT de turnos también deja al médico completar a mano (`confirmed` → `completed`); si ya está completado, firmar sigue valiendo. `updateDraft()` y `signIfDraft()` filtran `signed_at IS NULL`: lo firmado no se toca ni siquiera si un Service se saltea la validación.
- **Correcciones:** lo firmado se corrige con una evolución nueva (`corrects_id`), que reemplaza a la vigente del turno. Una sola vigente por turno (`findActiveByAppointment`).
- **No hay `DELETE`** ni endpoint público. No agregar uno sin decisión explícita.
- **Registro de accesos:** `EvolutionAccessLoggerService` registra cada lectura (listado y detalle) **después** de pasar los permisos, y **no atrapa fallos**: si el log no se guarda, la lectura falla (cierra seguro). Una lectura denegada no deja fila. No guarda contenido clínico.
- Toda query filtra por `tenant_id` (`TenantContext::id()`).

## Pendiente conocido
Datos clínicos en texto plano en la base: valorar cifrado de columnas o documentarlo como riesgo asumido.

## Tests
`tests/database/Evolution/EvolutionTest.php` (a nivel Service, sin HTTP): aislamiento por Tenant, ownership, roles, estado del turno, firma/inmutabilidad y registro de accesos. Correr con `--filter EvolutionTest` en el contenedor.

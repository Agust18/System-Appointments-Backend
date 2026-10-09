# DashboardDemoSeeder: no siembra horarios y oculta fallos de INSERT

- **Estado:** ⏳ Pendiente
- **Repo:** backend (seeder local, excluido de git en `.git/info/exclude`)
- **Fecha:** 07/10/2026
- **Archivo:** `app/Database/Seeds/DashboardDemoSeeder.php`

## 1. `medical_schedules` queda vacía (omisión, no falla)
El seeder nunca inserta en `medical_schedules` (no aparece en el archivo). Los 4 médicos sembrados no tienen horarios, así que:
- El dashboard y las métricas funcionan (los turnos se insertan directo).
- La disponibilidad (`/appointments/availability`, `/public/appointments/availability`) devuelve cero slots, y reservar un turno nuevo con esos médicos falla (el validador exige que el bloque quepa en un horario).
- El insert no tendría problema de FK: `medical_schedules.medical_id` apunta a `medicals.id`, que ya existe en ese punto, y lleva `tenant_id`.
- El `DashboardDemoCleanupSeeder` no necesita cambios: borrar los usuarios `@seed.local` elimina médicos y, en cascada, sus horarios.

**Arreglo:** después de insertar cada médico, insertar sus filas `(tenant_id, medical_id, day_of_week, start_time, end_time)` con `$medicalIds[$i]`. Los horarios deben cubrir los de los turnos generados (lunes a viernes 8-18, sábado 9-12).

## 2. Bug: dice "Sembrado OK" aunque un INSERT falle
Dentro de una transacción, CI4 no lanza ante una query fallida (`transException` es false por defecto en `BaseConnection::query`): marca `transStatus = false` y devuelve `false`. El seeder cierra con `transCommit()` (no `transComplete()`), que **commitea lo que sí entró**, y el mensaje final cuenta lo planeado (`count($medicals)`...), no lo insertado.
- Además, tras un INSERT fallido `insertID()` devuelve 0 o un id viejo, y los INSERT siguientes se encadenan con ids equivocados.
- **Arreglo:** chequear el retorno de cada `query()`, o usar `$this->db->transException(true)` antes de `transBegin()`, y comprobar `transStatus()` antes de commitear. Reportar el conteo real.

## 3. Valores fijos en vez de ids reales
Con la regla pedida ("usar el id real que devuelve el insert") el seeder ya cumple para `user_id`, `specialty_id`, `service_id`, `medical_id` y `patient_id`: todos salen de `insertID()`. **No hay `user_id` hardcodeado.** Lo que sí está fijo:
- **DNI:** `900000100 + i` (médicos) y `900000200 + i` (pacientes). Repetir la siembra, o sembrar dos veces en el mismo Tenant sin limpiar, choca con `uq_users_tenant_dni`; ese choque pasa silenciosamente por el bug 2.
- **`role_id`:** literales `2` y `3`, en vez de las constantes de `App\Constants\Role`.

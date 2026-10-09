# Glosario del dominio

Solo lenguaje de dominio. Las decisiones técnicas van en `adr/`.

| Término | Significado |
|---|---|
| **Tenant** | Organización cliente de la plataforma (hoy una clínica, pero el término es genérico y no depende del rubro). Sin sedes por ahora. Se identifica por su `slug` (subdominio). En la base: `tenant_id`. |
| **Paciente** | Persona atendida por un Tenant. Es un concepto del dominio actual (turnos de salud); no es el cliente de la plataforma: para eso se usa Tenant. |
| **SuperAdmin** | Operador de la plataforma. No pertenece a ningún Tenant. Da de alta Tenants, activa módulos y edita su configuración. |
| **Admin** | Usuario con rol ADMIN dentro de un Tenant. |
| **Núcleo** | Funciones siempre activas en todo Tenant: login, turnos, pacientes, médicos, servicios, especialidades, horarios. |
| **Módulo** | Función opcional que el SuperAdmin prende o apaga por Tenant: `public_booking`, `metrics`, `email_notifications` (futuros: `whatsapp`, `family_profiles`). |
| **Configuración del Tenant** | Valores editables por Tenant desde el panel: `name`, `logo_url`, `primary_color`, `reception_email`, `reception_email_enabled`, `reply_to_email`, `timezone`, `max_active_future_appointments`. |
| **Tenant suspendido** | Tenant con `status` inactivo (p. ej. falta de pago). Todas sus rutas responden 403; los datos se conservan. |
| **Usuario sin cuenta** | Paciente creado por reserva pública, sin contraseña (ver `.claude/rules/public-booking.md`). |

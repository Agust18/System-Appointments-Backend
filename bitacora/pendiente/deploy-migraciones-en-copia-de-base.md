# Deploy: migraciones probadas en una copia de la base

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

- [ ] **Migraciones probadas en una copia de la base** (UNIQUE de `users.dni` y `UNIQUE(tenant_id, dni)` de `patients`; `patients.user_id` ya no existe desde `paciente-sin-usuario`, fase 1; probar también `AddAccessToAppointments` y `RemovePatientRoleAndUsers`; los pre-checks abortan listando duplicados).

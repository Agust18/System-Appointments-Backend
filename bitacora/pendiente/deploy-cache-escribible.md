# Deploy: writable/cache escribible

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

- [ ] **`writable/cache` escribible** por el usuario del servidor. Si no lo es, CodeIgniter cae al handler `dummy` (`backupHandler`) y el throttle y el bloqueo por DNI **dejan de limitar sin ningún error** (decisión: falla abierto). El filtro `Throttle` deja un `critical` en el log en cada request en ese caso: revisar los logs después del primer despliegue.

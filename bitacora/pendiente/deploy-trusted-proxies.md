# Deploy: app.trustedProxies

- **Estado:** ⏳ Pendiente
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

- [ ] **`app.trustedProxies`** (y `app.proxyHeader` si no es `X-Forwarded-For`) configurado si hay un proxy delante. Sin eso todos los usuarios comparten la IP del proxy y el límite de reservas pasa a ser de todos juntos. Y el proxy tiene que **sobrescribir** el header, no agregarle (nginx: `proxy_set_header X-Forwarded-For $remote_addr;`): CodeIgniter toma la primera IP de la lista, que un cliente puede falsear para evadir el throttle.

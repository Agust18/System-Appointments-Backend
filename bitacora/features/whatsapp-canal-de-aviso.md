# WhatsApp como canal de aviso alternativo al email

- **Estado:** 🔵 Feature
- **Repo:** backend
- **Esfuerzo:** 8-12 h + configuración del proveedor
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔵 Features futuras (no empezar sin pedido) + PENDIENTES-GENERAL.md › 🟢 Futuro lejano

## Detalle

- **WhatsApp como canal de aviso alternativo al email.**
  - Costo: ~USD 0.005–0.05 por mensaje.
  - Esfuerzo: 8–12 h + configuración del proveedor.
  - Cuándo: con 10+ clientes.
  - Punto de entrada: `AppointmentNotificationService` (hoy envía por `EmailSenderService`; el `?callable $sender` ya desacopla el canal).

### Entrada original en PENDIENTES-GENERAL.md

| Feature | Cuándo | Repo | Esfuerzo | Notas |
|---|---|---|---|---|
| **WhatsApp recordatorios** | Add-on pagado | Back | — | Costo alto en Argentina (~$40 ARS/mensaje) |

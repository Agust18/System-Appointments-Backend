# Reserva pública: cómo limita (throttle por IP y bloqueo por DNI, Fase 4)

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** —
- **Origen:** PENDIENTES-BACKEND.md › 🔴 Reserva pública: checklist antes de exponerla a internet

## Detalle

La Fase 4 (throttle por IP + bloqueo por DNI) está **implementada**, pero los endpoints `/public/*` **NO deben exponerse a internet** hasta tildar todo esto:

**Cómo limita** (valores aprobados; constantes en `Routes.php` y `PublicIdentityAttemptLimiter`):
- `GET /public/*`: 60 por minuto por IP. `POST /public/appointments`: 5 por minuto por IP. El Throttler es un *token bucket*: "60 por minuto" = ráfaga de 60 y después 1 por segundo; "5 por minuto" = ráfaga de 5 y después 1 cada 12 s. El preflight `OPTIONS` no gasta cupo.
- **Bloqueo por DNI:** 5 teléfonos incorrectos para un DNI existente lo bloquean 1 hora. Se responde el mismo 409 neutral sin consultar la base, así que quien ataca no distingue "bloqueado" de "teléfono incorrecto". Una reserva exitosa reinicia la cuenta. Solo cuentan los rechazos por teléfono (no el email de otro usuario, ni el rol, ni un usuario inactivo).
- **Costo asumido:** alguien puede bloquear a propósito a un paciente real durante 1 hora (el paciente puede iniciar sesión o llamar al consultorio).

# Contrato: POST /patients/register (202) y UserResponse.hasAccount

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 02/10/2026
- **Origen:** PENDIENTES-FRONTEND.md (del repo backend, contrato de la API pública) › 🔴 Cambios de contrato (reserva pública, Fase 2)

## Detalle

> ⚠️ **Reemplazado (03/10/2026):** `POST /patients/register` ya **no devuelve 202**. Siempre responde `201` con el `PatientResponse`, también cuando el DNI pertenece a un usuario sin cuenta (mismo email guardado): se usa la contraseña del body y se envía el email de **verificación** (el usuario queda `is_verified = 0` y no puede iniciar sesión hasta verificar). El frontend debe quitar la rama `202` y no pedir la contraseña dos veces. El punto 1 de abajo queda solo como historia.

## 🔴 Cambios de contrato (reserva pública, Fase 2)

1. **`POST /patients/register` puede devolver `202` con `{ "message": "..." }`** en vez de `201` con el paciente.
   - Pasa cuando el DNI ya pertenece a un usuario **sin cuenta** (creado por una reserva pública o por recepción) y el email coincide con el guardado. No se crea nada y se le envía un link a su email para que elija su contraseña.
   - El frontend debe tratar el `202` como éxito y mostrar el `message` ("Te enviamos un email para que actives tu cuenta"). **No** redirigir al login ni asumir que el body trae un paciente: no hay `id`.
   - Distinguir por status, no por la forma del body:
     ```ts
     const res = await api.post('/patients/register', body);
     if (res.status === 202) return showInfo(res.data.message); // sin cuenta: link por email
     return onRegistered(res.data);                              // 201: paciente creado
     ```
   - La contraseña enviada en el body se ignora en ese caso: la fija el usuario desde el link.
2. **`UserResponse` suma `hasAccount: boolean`.** Es aditivo. `false` = usuario sin contraseña (no puede iniciar sesión hasta activar su cuenta). Sirve para mostrar un badge "sin cuenta" a recepción/admin.

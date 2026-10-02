# PENDIENTES — FRONTEND

Última actualización: 02/10/2026

Cambios de contrato del backend que el frontend tiene que tolerar o aprovechar.

---

## 🔴 Cambios de contrato (reserva pública, Fase 2)

1. **`POST /patients/register` puede devolver `202` con `{ "message": "..." }`** en vez de `201` con el paciente.
   - Pasa cuando el DNI ya pertenece a un usuario **sin cuenta** (creado por una reserva pública o por recepción) y el email coincide con el guardado. No se crea nada y se le envía un link a su email para que elija su contraseña.
   - El frontend debe tratar el `202` como éxito y mostrar el `message` ("Te enviamos un email para que actives tu cuenta").
   - La contraseña enviada en el body se ignora en ese caso: la fija el usuario desde el link.
2. **`UserResponse` suma `hasAccount: boolean`.** Es aditivo. `false` = usuario sin contraseña (no puede iniciar sesión hasta activar su cuenta). Sirve para mostrar un badge "sin cuenta" a recepción/admin.

---

## 🟡 También cambió (Fases 1-2)

- **DNI y teléfono se normalizan en el backend** (se guardan solo con dígitos). El frontend puede mandarlos con puntos, guiones o espacios y debe **formatearlos solo para mostrar**.
  - DNI válido: 7 a 8 dígitos (antes el registro público exigía exactamente 8).
  - Teléfono válido: solo dígitos, 10 a 15 (ej. `2325506499`). Se quitan `+54`, `549` y el `0` de área. El "15" móvil no se resuelve: mostrar un hint tipo "código de área, sin 0 ni 15".
- **El email se guarda en minúsculas y sin espacios.**
- **`POST /patients` (recepción/admin)**: `password` y `patient.birthDate` pasan a ser opcionales. Sin contraseña el paciente nace sin cuenta.
- **`PatientResponse.birthDate` y `PatientProjectionResponse.birthDate` pueden ser `null`** (la reserva pública no pide fecha de nacimiento).
- **`PUT /patients/{id}`** acepta además `firstName`, `lastName`, `phone` y `email` (todos opcionales). Permite a recepción completar o corregir el teléfono de un paciente.
- **Un paciente existente sin teléfono no podrá usar la reserva pública** hasta que recepción cargue su teléfono. Conviene mostrar `phone` vacío como dato faltante en la ficha del paciente.

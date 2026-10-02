# Contrato: cambios de las Fases 1-2 (normalización de DNI, teléfono y email)

- **Estado:** ✅ Hecho
- **Repo:** backend
- **Esfuerzo:** —
- **Fecha:** 02/10/2026
- **Origen:** PENDIENTES-FRONTEND.md (del repo backend, contrato de la API pública) › 🟡 También cambió (Fases 1-2)

## Detalle

## 🟡 También cambió (Fases 1-2)

- **DNI y teléfono se normalizan en el backend** (se guardan solo con dígitos). El frontend puede mandarlos con puntos, guiones o espacios y debe **formatearlos solo para mostrar**.
  - DNI válido: 7 a 8 dígitos (antes el registro público exigía exactamente 8).
  - Teléfono válido: solo dígitos, 10 a 15 (ej. `2325506499`). Se quitan `+54`, `549` y el `0` de área. El "15" móvil no se resuelve: mostrar un hint tipo "código de área, sin 0 ni 15".
- **El email se guarda en minúsculas y sin espacios.**
- **`POST /patients` (recepción/admin)**: `password` y `patient.birthDate` pasan a ser opcionales. Sin contraseña el paciente nace sin cuenta.
- **`PatientResponse.birthDate` y `PatientProjectionResponse.birthDate` pueden ser `null`** (la reserva pública no pide fecha de nacimiento).
- **`PUT /patients/{id}`** acepta además `firstName`, `lastName`, `phone` y `email` (todos opcionales). Permite a recepción completar o corregir el teléfono de un paciente.
- **Un paciente existente sin teléfono no podrá usar la reserva pública** hasta que recepción cargue su teléfono. Conviene mostrar `phone` vacío como dato faltante en la ficha del paciente.

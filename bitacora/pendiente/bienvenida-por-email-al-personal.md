# Email de bienvenida al personal (elegir su propia contraseña)

- **Estado:** ⏳ Pendiente (idea; falta decidir)
- **Repo:** backend + frontend (`System-AppointmentsFrontend`)
- **Esfuerzo:** ~0,5–1 día
- **Fecha:** 2026-10-21
- **Origen:** al terminar la fase 2 de `paciente-sin-usuario.md` quedó a la vista una asimetría entre las altas.

## Situación actual
| Alta | Contraseña | Email |
|---|---|---|
| Personal (admin/recepción/médico) dado de alta por admin o recepción | **Obligatoria**, la escribe quien da el alta | **Ninguno**: se la pasan por fuera del sistema |
| Primer ADMIN de una clínica, dado de alta por el superadmin | Opcional | **Siempre** email de bienvenida con enlace (72 h) para elegirla |
| "Olvidé mi contraseña" | — | Enlace de 1 h, mismo mail para todos |

## Idea
Que el personal nuevo también reciba un email de bienvenida con un enlace para **elegir su propia contraseña**, de modo que quien lo da de alta no la conozca ni tenga que pasársela.

## Decisiones a tomar
- ¿La contraseña pasa a ser **opcional** en el alta del personal (como en la del primer ADMIN) o se mantiene obligatoria y el mail es solo un extra?
- ¿Qué pasa si el usuario no tiene email? (hoy el email del personal es obligatorio, así que no debería ocurrir).
- ¿Qué pasa con el mail si falla el envío? (el primer ADMIN ya resuelve esto: el alta no se deshace y se puede pedir otro enlace con "olvidé mi contraseña").
- Reutilizar el servicio y el token del primer ADMIN (`SendTenantAdminWelcomeEmailService` / `TenantAdminWelcomeTokenService`, enlace de 72 h) en vez de crear otros.

## Pasos probables
- [ ] Backend: al crear un usuario del personal sin contraseña, generar el token de bienvenida y enviar el mail (después de confirmar el alta; si falla el envío, no se deshace).
- [ ] Backend: `UserRequest` con contraseña opcional para ese alta, y revisar que un usuario sin contraseña no pueda iniciar sesión hasta elegirla (hoy `verifyPassword` ya lo rechaza).
- [ ] Frontend: `UserForm`/alta de profesionales con contraseña opcional y aviso de que se le enviará un enlace.
- [ ] Tests: alta sin contraseña, mail enviado, falla del mail sin deshacer el alta, login rechazado antes de elegir contraseña.
- [ ] Actualizar `email.md` y `auth-rbac.md`.

## Riesgos / puntos a vigilar
- Hoy `hasAccount()` y el mail "Activá tu cuenta" se borraron (fase 2); este pendiente no los necesita: el mail de bienvenida ya existe como plantilla aparte.
- Un usuario creado sin contraseña y con un email mal escrito queda inaccesible hasta que se corrija el email.

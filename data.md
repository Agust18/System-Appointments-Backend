Consideraciones de seguridad:
-Validar que el usuario que hace los cambios sea admin, donde iria?


Módulos secundarios (opcionales):
Módulo	-  ¿Qué muestra?	 - ¿Acciones?
Horarios  Horarios de atención por médico	Configurar horarios
Reportes  Turnos por período, médicos más solicitados	-   Generar PDF/Excel
Configuración	Datos de la clínica	Editar

NUEVAS OPCIONES: 
VALIDATORS PARA CADA SERVICIO YA SEA UPDATER,DELETER ETC

mejoras futuras: 
-historial medico : id_patient id_doc texto fecha y hora. 
- mejorar form de turnos,casillas de horarios.
-cambiar phone a usuario
-detalles :metricas, stats, whatsapp

//updates
quitar filtro de patient en appointment y relacionado.



1. Dashboard del Paciente
¿Tiene sentido que el paciente tenga un Dashboard con estadísticas?

No. Para el paciente, las estadísticas (como "cuántos turnos sacó en el año") son irrelevantes. Lo que necesita es claridad inmediata sobre sus compromisos médicos actuales y la opción rápida de sacar un nuevo turno.

Lo que DEBE mostrar:

Próximo Turno (Tarjeta Principal / Destacada): Fecha, hora, nombre del médico, especialidad y dirección/consultorio (o link a videollamada si es online). Opción clara de "Cancelar" o "Reprogramar".

Acceso Rápido (CTA Principal): Botón grande e inconfundible: "Solicitar Nuevo Turno".

Historial Reciente (Lista corta): Los últimos 2 o 3 turnos pasados con opción de descargar comprobante o ver indicaciones.

Notificaciones o Avisos: Por ejemplo: "Tienes una receta pendiente de descarga" o "Recuerda asistir 10 min antes".

2. Dashboard del Médico
Como su primera vista principal ya es el Calendario con los turnos del día, el Dashboard actúa como su centro de comando operativo para la jornada.

Lo que DEBE mostrar:

Resumen del Día (Métricas rápidas arriba):

Total de turnos hoy (ej: 12 turnos).

Atendidos / Pendientes / Cancelados.

Próximo paciente en sala de espera.

Lista de Pacientes del Día (Orden cronológico): Una vista tipo lista o tarjetas del día actual para ver rápido a quién le toca sin navegar en la agenda del calendario completa.

Acción Directa: Botón de "Iniciar Atención" o "Ver Historia Clínica" directo desde la tarjeta del paciente de la lista.

3. Dashboard del Administrador / Recepción
A este rol SÍ le interesan las estadísticas globales y la gestión de la clínica. Es la única vista donde las métricas descriptivas tienen valor estratégico.

Lo que DEBE mostrar:

Métricas Globales (Tarjetas de Stats):

Total de turnos otorgados en el mes/semana.

Tasa de ausentismo (% de turnos cancelados o pacientes que no asistieron).

Especialidades más demandadas.

Turnos Recientes / En Vivo: Tabla de turnos que se están reservando en tiempo real o el estado actual de las salas de espera.

Alertas del Sistema: Médicos ausentes, horarios sin cubrir o solicitudes de reprogramación pendientes.
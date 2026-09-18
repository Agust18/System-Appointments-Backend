# Informe de auditoría estática

Fecha: 2026-09-16
Proyecto: System-Appointments-Backend

## Alcance

Análisis de lectura sobre rutas, controllers, services, models, proyecciones, migraciones y pruebas. No se modificó código de la aplicación.

## Hallazgos principales

### 1. Duplicación en la lógica de reservas

`AppointmentBookingService` y `AppointmentUpdateBookingService` repiten:

- adquisición y liberación de locks MySQL;
- inicio, confirmación y rollback de transacciones;
- carga del servicio y de la relación médico-servicio;
- cálculo de duración efectiva y buffer;
- cálculo de hora final;
- validación de solapamientos.

Referencias:

- `app/Services/Appointment/AppointmentBookingService.php`
- `app/Services/Appointment/AppointmentUpdateBookingService.php`

Sugerencia: extraer un componente común para el lock, el cálculo del bloque y las operaciones compartidas. La actualización debe seguir excluyendo el turno que está editando.

### 2. Servicios probablemente sin uso interno

Estas clases solo aparecen en su propia definición dentro del repositorio:

- `app/Services/Medical/MedicalsSearcherService.php`
- `app/Services/Patient/PatientsSearcherService.php`

Los controllers de listado utilizan las versiones de `Projection...SearcherService`. Estas clases parecen restos de una implementación anterior.

Nota: el análisis estático no puede detectar consumidores externos que instancien clases directamente.




## Optimizaciones recomendadas

1. Centralizar el cálculo de duración efectiva y buffer del servicio.
2. Centralizar la adquisición y liberación de locks.
3. Evaluar la eliminación de searchers normales que fueron reemplazados por searchers de proyección.
4. Evitar mantener simultáneamente endpoints específicos y filtros equivalentes para citas, salvo que exista una necesidad clara de compatibilidad.
5. Revisar índices para las consultas más frecuentes. En particular, puede ser útil un índice compuesto para `medical_schedules` sobre médico, día y estado activo.
6. Revisar búsquedas con `LIKE '%texto%'`, ya que normalmente no aprovechan índices B-Tree convencionales.
7. Reducir los joins de las consultas `countProjection()` cuando no sean necesarios para aplicar filtros.
8. Añadir pruebas específicas para reservas concurrentes, disponibilidad, solapamientos, permisos, filtros y proyecciones.

## SUGERENCIAS DE MEJORAS

### Prioridad alta



#### 1. Unificar la lógica de reservas y actualizaciones

Problema: la creación y la actualización repiten reglas críticas de concurrencia y cálculo de horarios.

Cómo mejorarlo:

1. Crear un servicio interno para resolver el servicio activo, la relación médico-servicio y la duración efectiva.
2. Crear otro componente reutilizable para adquirir y liberar el lock por médico y fecha.
3. Mantener `AppointmentBookingService` y `AppointmentUpdateBookingService` como coordinadores del caso de uso.
4. Mantener la exclusión del identificador actual en la actualización para no detectar el propio turno como conflicto.
5. Probar dos reservas simultáneas para el mismo médico, fecha y horario.

#### 2. Validar la transacción y los locks con pruebas reales

Problema: la disponibilidad se consulta antes de reservar y puede cambiar entre ambas operaciones.

Cómo mejorarlo:

1. Mantener la comprobación final dentro de la transacción y bajo el lock.
2. Comprobar explícitamente el estado de la transacción antes de confirmar.
3. Garantizar la liberación del lock incluso ante excepciones.
4. Añadir una prueba de concurrencia que confirme que solo una reserva puede insertarse.
5. Verificar que el mecanismo elegido funciona únicamente con MySQL, ya que `GET_LOCK()` no es portable a todos los motores.

### Prioridad media

#### 3. Mejorar las consultas de paginación

Problema: los listados ejecutan una consulta de datos y otra consulta de conteo con joins que pueden ser innecesarios.

Cómo mejorarlo:

1. Construir una función común para generar filtros y parámetros sin duplicar reglas.
2. Usar en el `COUNT` solamente los joins necesarios para los filtros activos.
3. Revisar el plan de ejecución con `EXPLAIN` en las consultas más usadas.
4. Medir el tiempo de respuesta con tablas pequeñas y con datos representativos.

### Prioridad baja

#### 4. Añadir índices orientados a las consultas reales

Cómo mejorarlo:

1. Revisar las consultas de disponibilidad y horarios.
2. Evaluar un índice compuesto en `medical_schedules` sobre `medical_id`, `day_of_week` e `is_active`.
3. Mantener el índice existente de `appointments(medical_id, fecha, status)` y comprobar si cubre las consultas de solapamiento.
4. Revisar índices para filtros por `specialty_id`, `user_id` y relaciones de `medical_service`.
5. Confirmar cada índice con `EXPLAIN`; no añadir índices solo por intuición.

#### 5. Revisar las búsquedas de texto

Problema: las búsquedas con `LIKE '%texto%'` pueden provocar recorridos completos de tabla.

Cómo mejorarlo:

1. Medir el rendimiento con volúmenes de datos realistas.
2. Para búsquedas simples, considerar prefijos (`texto%`) cuando el comportamiento funcional lo permita.
3. Para búsquedas generales, evaluar índices FULLTEXT o un buscador especializado.
4. Limitar la cantidad máxima de resultados y validar los parámetros de paginación.

#### 6. Reforzar las pruebas del dominio

Cómo mejorarlo:

1. Crear pruebas para crear, actualizar, cancelar y eliminar turnos.
2. Cubrir horarios con múltiples rangos, buffers y duraciones personalizadas.
3. Cubrir conflictos de horarios y reservas concurrentes.
4. Probar permisos por rol y filtros de autenticación.
5. Probar las proyecciones y los casos de columnas nulas.
6. Ejecutar las pruebas en PHP 8.2 o superior, que es la versión requerida por `composer.json`.

#### 7. Revisar la instanciación manual de servicios

Problema: muchos controllers crean directamente sus servicios con `new`, lo que dificulta sustituir dependencias y probar casos aislados.

Cómo mejorarlo:

1. Registrar servicios compartidos mediante el mecanismo de servicios de CodeIgniter.
2. Inyectar dependencias cuando el framework y la arquitectura lo permitan.
3. Evitar crear repetidamente modelos y converters dentro de cada operación.
4. Aplicar este cambio de forma gradual, empezando por reservas y disponibilidad.

#### 12. Mantener el esquema y el código sincronizados

Cómo mejorarlo:

1. Tratar las migraciones como la fuente versionada del esquema.
2. No cambiar nombres de columnas manualmente fuera de una migración.
3. Añadir una comprobación de integración que ejecute las consultas principales contra una base de datos de prueba.
4. Documentar cualquier diferencia entre la base de datos local, Docker y producción.

## Cobertura observada

Las pruebas actuales son principalmente las pruebas iniciales de CodeIgniter. No se observaron pruebas específicas para las reglas de negocio del sistema de turnos.

Archivos relevantes:

- `tests/unit/HealthTest.php`
- `tests/database/ExampleDatabaseTest.php`
- `tests/session/ExampleSessionTest.php`

## Limitaciones del análisis

- No se pudo ejecutar `php spark routes` porque el entorno tiene PHP 8.1 y el proyecto requiere PHP 8.2 o superior.
- No es posible confirmar mediante el repositorio si un endpoint es utilizado por un frontend o cliente externo.
- El repositorio ya tenía cambios locales antes de este informe. No fueron modificados.

## Conclusión

La mayor oportunidad de mantenimiento está en unificar la lógica de reservas y decidir si las búsquedas específicas de citas deben coexistir con el endpoint filtrable. También conviene revisar primero la consulta de proyección de citas, porque la diferencia entre `matricula`/`tuition` y `nombre`/`name` parece un problema funcional, no solamente una optimización.

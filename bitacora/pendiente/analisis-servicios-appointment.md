Contexto:
Sistema de booking médico en CodeIgniter 4, PHP 8.2. Módulo principal:
app/Services/Appointment/.

El módulo creció a ~25 archivos y no logro entender el flujo. Sospecho
que hay:
- Servicios duplicados que hacen lo mismo (Creator vs Booking vs PublicCreator).
- Validators que quizás sobran por ser triviales.
- Lógica común duplicada entre flujo autenticado y flujo público.
- Nombres confusos (ServiceDuration vs ServiceDurationCalculatorService).

Lo que necesito (ANÁLISIS, NO CÓDIGO):
1. Leé todos los archivos de app/Services/Appointment/, más los Controllers,
   Requests y Models que los consumen.
2. Hacé un mapa del flujo actual de cada caso de uso principal:
   - Crear turno (autenticado)
   - Crear turno (público)
   - Actualizar turno
   - Eliminar turno
   - Consultar disponibilidad
   Para cada uno: qué Controller entra, qué Request valida, qué Service
   orquesta, qué Validator aplica, qué Model toca, qué Converter transforma.
3. Identificá:
   a) Lógica duplicada entre servicios (con archivo y línea).
   b) Servicios que se pueden fusionar sin perder separación de flujos.
   c) Validators que sobran (< 15 líneas, regla trivial).
   d) Nombres confusos y propuesta de renombrado.
   e) Acoplamientos raros o dependencias circulares.
4. Proponé un rediseño del módulo, con:
   - Qué archivos se fusionan.
   - Qué archivos se borran.
   - Qué archivos se quedan (y por qué).
   - Qué archivos nuevos aparecen (si alguno).
   - Cómo se mantiene la separación público/auth.

Restricciones:
- No escribas código. Solo análisis y propuesta.
- No propongas cambios que rompan tests sin decirlo.
- Si algo no lo podés determinar leyendo el código, decilo.

Empezá listando los archivos que vas a leer y por qué. Después el mapa
de flujos. Después el análisis. Al final la propuesta.
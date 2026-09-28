# Métricas del dashboard

5 endpoints de solo lectura para alimentar el dashboard del frontend
(`MetricsService.js`). Permisos: `['auth', 'adminOrReception']` (ADMIN o
RECEPTION). Namespace: Controllers en `app/Controllers/Metrics/`, Services en
`app/Services/Metrics/`, un único `MetricsModel` en `app/Models/Metrics/`.

| Ruta | Params | Nota |
|---|---|---|
| `GET /metrics/kpis` | `dateFrom`, `dateTo`, `medicalId` (todos opcionales) | `total, pending, confirmed, cancelled, completed, noshow, revenue` |
| `GET /metrics/by-medical` | `dateFrom`, `dateTo` | **No** acepta `medicalId` (el gráfico ES por médico) |
| `GET /metrics/monthly-trend` | `medicalId` (opcional) | Ventana fija de 6 meses, **no** acepta `dateFrom`/`dateTo` |
| `GET /metrics/top-services` | `dateFrom`, `dateTo`, `medicalId` | Top 10 ordenado desc |
| `GET /metrics/schedule-heatmap` | `dateFrom`, `dateTo`, `medicalId` | Solo combinaciones con `total > 0` |

## Reglas de negocio

- **No-show es derivado**, no un estado nuevo: un turno es no-show si
  `status = 'confirmed'` y su fecha/hora ya pasó. Se calcula en SQL con
  `CONCAT(fecha, ' ', hora) < NOW()`, nunca se persiste.
- **`confirmed` y `noshow` son mutuamente excluyentes** en `/metrics/kpis`:
  `confirmed` = turnos `confirmed` con fecha/hora futura; `noshow` = turnos
  `confirmed` con fecha/hora pasada. Un turno cae en uno o en el otro, nunca
  en ambos.
- **`top-services` y `schedule-heatmap` excluyen `cancelled`** (cuentan
  `pending`, `confirmed`, `completed`) porque buscan reflejar demanda real y
  carga horaria efectiva, no intención de reserva. El resto de los endpoints
  (`kpis`, `by-medical`) cuentan todos los status.
- **Revenue** usa el precio efectivo: `COALESCE(medical_service.custom_price,
  services.price)`, sumado solo sobre turnos `completed`.
- **Rango de fechas por defecto**: si no viene `dateFrom`, se usa el primer
  día del mes actual; si no viene `dateTo`, se usa hoy. Se valida el formato
  `YYYY-MM-DD` en `MetricsDateRangeResolverService`
  (`App\Services\Metrics\MetricsDateRangeResolverService`), que lanza
  `App\Exception\Validation\ValidationException` (422) si el formato es
  inválido. No se usa en `monthly-trend`, que calcula su propia ventana fija.
- **Single-tenant**: no hay filtro por consultorio.

## Arquitectura

Todos los endpoints comparten un único `App\Dto\Request\Metrics\MetricsFilterRequest`
(`dateFrom`, `dateTo`, `medicalId`, todos opcionales); cada Controller solo
llena los campos que su endpoint acepta según el contrato de arriba (ej.
`by-medical` nunca lee `medicalId` de la query).

Las 5 entidades de resultado (`MetricsKpisProjection`, `MedicalMetricsProjection`,
`MonthlyTrendProjection`, `TopServiceProjection`, `ScheduleHeatmapProjection`)
viven en `app/Entity/Metrics/Projection/` porque son de solo lectura y nunca
se persisten, con su Converter `PrimitiveToXProjectionConverter` (stdClass →
Entity, usado en `MetricsModel`) y `XProjectionToXResponseConverter` (Entity →
Response DTO, usado en cada Service) en `app/Converter/Metrics/Projection/`.

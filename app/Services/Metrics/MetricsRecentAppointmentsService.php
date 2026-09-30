<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsRecentAppointmentsService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array<int, array{appointmentId: int, patientName: string, medicalName: string, serviceName: ?string, fecha: string, hora: string, status: string, createdAt: ?string, updatedAt: ?string}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getRecentAppointments($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(fn (object $row) => $this->buildItem($row), $rows);
    }

    private function buildItem(object $row): array
    {
        return [
            'appointmentId' => (int) $row->appointment_id,
            'patientName' => $row->patient_name,
            'medicalName' => $row->medical_name,
            'serviceName' => $row->service_name,
            'fecha' => $row->fecha,
            'hora' => $row->hora,
            'status' => $row->status,
            'createdAt' => $row->created_at,
            'updatedAt' => $row->updated_at,
        ];
    }
}

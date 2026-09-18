<?php

namespace App\Services\Appointment;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;

final class AppointmentStatsService
{
    private AppointmentModel $appointmentModel;
    private MedicalModel $medicalModel;
    private PatientModel $patientModel;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->medicalModel = new MedicalModel();
        $this->patientModel = new PatientModel();
    }

    public function getStats(int $roleId, int $userId, ?string $fecha = null): array
    {
        $medicalId = null;
        $patientId = null;

        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            $medicalId = $medical?->getId();
        }

        if ($roleId === Role::PATIENT) {
            $patient = $this->patientModel->findByUserId($userId);
            $patientId = $patient?->getId();
        }

        $filters = new AppointmentFilterRequest(
            pagination: new PaginationRequest(1, 10),
            medicalId: $medicalId,
            patientId: $patientId,
            fecha: $fecha ?? date('Y-m-d')
        );

        $byStatus = $this->appointmentModel->statsByStatus($filters);

        return [
            'todayTotal'     => array_sum($byStatus),
            'todayPending'   => $byStatus['pending'],
            'todayConfirmed' => $byStatus['confirmed'],
            'todayCancelled' => $byStatus['cancelled'],
            'todayCompleted' => $byStatus['completed'],
        ];
    }
}

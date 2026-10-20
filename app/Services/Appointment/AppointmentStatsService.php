<?php

namespace App\Services\Appointment;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;

final class AppointmentStatsService
{
    private AppointmentModel $appointmentModel;
    private MedicalModel $medicalModel;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->medicalModel = new MedicalModel();
    }

    public function getStats(int $roleId, int $userId, ?string $fecha = null): array
    {
        $medicalId = null;
        $patientId = null;

        // Sin fila propia el filtro quedaría en null (= sin filtro): falla cerrado.
        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            if ($medical === null) {
                throw new MedicalNotFoundExceptionByUserException($userId);
            }
            $medicalId = $medical->getId();
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

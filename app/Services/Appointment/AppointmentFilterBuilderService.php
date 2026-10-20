<?php
namespace App\Services\Appointment;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Medical\MedicalModel;

final class AppointmentFilterBuilderService
{
    private MedicalModel $medicalModel;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
    }

    public function build(
        PaginationRequest $pagination,
        ?int $medicalId,
        ?int $patientId,
        ?string $status,
        ?string $fecha,
        ?string $search
    ): AppointmentFilterRequest {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');




        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            if ($medical === null) {
                throw new MedicalNotFoundExceptionByUserException($userId);
            }
            $medicalId = $medical->getId();
            $patientId = null;
        }


        return new AppointmentFilterRequest(
            $pagination,
            $medicalId,
            $patientId,
            $status,
            $fecha,
            $search
        );
    }
}
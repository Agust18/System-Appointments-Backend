<?php
namespace App\Services\Appointment;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Exception\Patient\PatientNotFoundExceptionByUserException;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;

final class AppointmentFilterBuilderService
{
    private PatientModel $patientModel;
    private MedicalModel $medicalModel;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
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


        // Sin fila propia el filtro quedaría en null (= sin filtro) y vería los turnos de todos: falla cerrado.
        if ($roleId === Role::PATIENT) {
            $patient = $this->patientModel->findByUserId($userId);
            if ($patient === null) {
                throw new PatientNotFoundExceptionByUserException($userId);
            }
            $patientId = $patient->getId();
            $medicalId = null;
        }

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
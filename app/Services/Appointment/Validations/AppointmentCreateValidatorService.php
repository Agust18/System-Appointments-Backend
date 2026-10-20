<?php
namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\InvalidInitialStatusException;
use App\Exception\Appointment\MedicalCannotCreateAppointmentException;
use App\Services\Patient\PatientFinderService;

final class AppointmentCreateValidatorService
{
    private AppointmentMedicalActiveValidatorService $medicalActiveValidator;
    private PatientFinderService $patientFinderService;
    private AppointmentSlotValidatorService $slotValidator;

    public function __construct()
    {
        $this->medicalActiveValidator = new AppointmentMedicalActiveValidatorService();
        $this->patientFinderService = new PatientFinderService();
        $this->slotValidator = new AppointmentSlotValidatorService();
    }

    public function validate(AppointmentRequest $request): void
    {

        $userRoleId = session()->get('roleId');
        if ($userRoleId === Role::MEDICAL) {
            throw new MedicalCannotCreateAppointmentException();
        }
        if ($userRoleId === Role::RECEPTION) {
            if ($request->getStatus() !== 'confirmed') {
                throw new InvalidInitialStatusException('recepcionistas');
            }
        }

        $user = $this->medicalActiveValidator->validate($request->getMedicalId());
        $doctorName = $user->getFullName();

        $this->patientFinderService->find($request->getPatientId());

        $this->slotValidator->validate(
            $request->getMedicalId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $doctorName
        );
    }
}

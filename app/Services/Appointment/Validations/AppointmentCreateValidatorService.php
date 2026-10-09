<?php
namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\CannotCreateAppointmentForOtherPatientException;
use App\Exception\Appointment\InvalidInitialStatusException;
use App\Exception\Appointment\MedicalCannotCreateAppointmentException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\PatientCannotBeMedicalException;
use App\Exception\Patient\UserNotPatientException;
use App\Services\Medical\MedicalFinderService;
use App\Services\Patient\PatientFinderService;
use App\Services\User\UserFinderService;

final class AppointmentCreateValidatorService
{
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;
    private PatientFinderService $patientFinderService;
    private AppointmentSlotValidatorService $slotValidator;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->patientFinderService = new PatientFinderService();
        $this->slotValidator = new AppointmentSlotValidatorService();
    }

    public function validate(AppointmentRequest $request): void
    {

        $userRoleId = session()->get('roleId');
        if ($userRoleId === Role::MEDICAL) {
            throw new MedicalCannotCreateAppointmentException();
        }
        if ($userRoleId === Role::PATIENT) {
            if ($request->getStatus() !== 'pending') {
                throw new InvalidInitialStatusException('pacientes');
            }
        }
        if ($userRoleId === Role::RECEPTION) {
            if ($request->getStatus() !== 'pending') {
                throw new InvalidInitialStatusException('recepcionistas');
            }
        }

        $medical = $this->medicalFinderService->find($request->getMedicalId());

        $user = $this->userFinderService->find($medical->getUserId());

        if (!$user->getIsActive()) {
            throw new MedicalNotAvailableException();
        }

        $doctorName = $user ? $user->getFullName() : 'Profesional';

        $patient = $this->patientFinderService->find($request->getPatientId());

        $patientUser = $this->userFinderService->find($patient->getUserId());

        if ($userRoleId === Role::PATIENT) {
            $currentUserId = session()->get('userId');

            if ($patient->getUserId() !== $currentUserId) {
                throw new CannotCreateAppointmentForOtherPatientException();
            }
        }
        if ($patientUser->getRoleId() !== Role::PATIENT) {
            throw new UserNotPatientException();
        }

        // Validar que el paciente no sea el mismo que el médico
        if ($patientUser->getId() === $user->getId()) {
            throw new PatientCannotBeMedicalException();
        }

        $this->slotValidator->validate(
            $request->getMedicalId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $doctorName
        );
    }
}

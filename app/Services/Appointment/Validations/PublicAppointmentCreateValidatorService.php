<?php
namespace App\Services\Appointment\Validations;

use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;

/**
 * Reglas de la reserva pública que no dependen del paciente. No lee la sesión:
 * quien reserva no está autenticado. Corre antes de resolver la identidad, de
 * modo que si algo falla no se crea ningún usuario ni paciente.
 */
final class PublicAppointmentCreateValidatorService
{
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;
    private AppointmentSlotValidatorService $slotValidator;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->slotValidator = new AppointmentSlotValidatorService();
    }

    public function validate(PublicAppointmentRequest $request): void
    {
        $medical = $this->medicalFinderService->find($request->getMedicalId());
        $doctor = $this->userFinderService->find($medical->getUserId());

        if (!$doctor->getIsActive()) {
            throw new MedicalNotAvailableException();
        }

        $this->slotValidator->validate(
            $request->getMedicalId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $doctor->getFullName()
        );
    }
}

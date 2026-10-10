<?php
namespace App\Services\Appointment\Validations;

use App\Dto\Request\Appointment\PublicAppointmentRequest;

/**
 * Reglas de la reserva pública que no dependen del paciente. No lee la sesión:
 * quien reserva no está autenticado. Corre antes de resolver la identidad, de
 * modo que si algo falla no se crea ningún usuario ni paciente.
 */
final class PublicAppointmentCreateValidatorService
{
    private AppointmentMedicalActiveValidatorService $medicalActiveValidator;
    private AppointmentSlotValidatorService $slotValidator;

    public function __construct()
    {
        $this->medicalActiveValidator = new AppointmentMedicalActiveValidatorService();
        $this->slotValidator = new AppointmentSlotValidatorService();
    }

    public function validate(PublicAppointmentRequest $request): void
    {
        $doctor = $this->medicalActiveValidator->validate($request->getMedicalId());

        $this->slotValidator->validate(
            $request->getMedicalId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $doctor->getFullName()
        );
    }
}

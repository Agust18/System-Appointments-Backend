<?php
namespace App\Services\Evolution\Validations;

use App\Dto\Request\Evolution\EvolutionRequest;
use App\Entity\Evolution\Evolution;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Exception\Evolution\EvolutionAccessDeniedException;
use App\Exception\Evolution\EvolutionAlreadyExistsException;
use App\Exception\Evolution\EvolutionAppointmentNotEligibleException;
use App\Exception\Evolution\EvolutionInvalidCorrectionException;
use App\Models\Evolution\EvolutionModel;
use App\Services\Appointment\AppointmentFinderService;
use App\Services\Evolution\EvolutionFinderService;
use App\Services\Patient\PatientFinderService;
use App\Services\Permissions\PermissionsService;

final class EvolutionCreateValidatorService
{
    private const ELIGIBLE_STATUSES = ['confirmed', 'completed'];

    private PatientFinderService $patientFinder;
    private AppointmentFinderService $appointmentFinder;
    private EvolutionFinderService $evolutionFinder;
    private EvolutionModel $evolutionModel;
    private PermissionsService $permissionsService;

    public function __construct()
    {
        $this->patientFinder = new PatientFinderService();
        $this->appointmentFinder = new AppointmentFinderService();
        $this->evolutionFinder = new EvolutionFinderService();
        $this->evolutionModel = new EvolutionModel();
        $this->permissionsService = new PermissionsService();
    }

    public function validate(EvolutionRequest $request, int $patientId, int $roleId, int $userId): void
    {
        $this->patientFinder->find($patientId);

        $appointment = $this->appointmentFinder->find($request->getAppointmentId());

        // un turno de otro paciente responde igual que uno inexistente
        if ($appointment->getPatientId() !== $patientId) {
            throw new AppointmentNotFoundException($request->getAppointmentId());
        }

        if (!$this->permissionsService->canWriteEvolution($roleId, $userId, $appointment)) {
            throw new EvolutionAccessDeniedException('Solo el médico del turno puede registrar su evolución.');
        }

        if (!in_array($appointment->getStatus(), self::ELIGIBLE_STATUSES, true)) {
            throw new EvolutionAppointmentNotEligibleException($appointment->getStatus());
        }

        $active = $this->evolutionModel->findActiveByAppointment($request->getAppointmentId());

        if ($request->getCorrectsId() === null) {
            if ($active !== null) {
                throw new EvolutionAlreadyExistsException($request->getAppointmentId());
            }
            return;
        }

        $this->validateCorrection($request, $active);
    }

    /** Una corrección reemplaza a la evolución vigente del mismo turno, y esa tiene que estar firmada. */
    private function validateCorrection(EvolutionRequest $request, ?Evolution $active): void
    {
        $corrected = $this->evolutionFinder->find($request->getCorrectsId());

        if ($corrected->getAppointmentId() !== $request->getAppointmentId()) {
            throw new EvolutionInvalidCorrectionException('La evolución a corregir no pertenece a ese turno.');
        }
        if (!$corrected->isSigned()) {
            throw new EvolutionInvalidCorrectionException('Solo se corrige una evolución firmada; un borrador se edita directamente.');
        }
        if ($active === null || $active->getId() !== $corrected->getId()) {
            throw new EvolutionInvalidCorrectionException('Esa evolución ya fue corregida. Corregí la versión vigente.');
        }
    }
}

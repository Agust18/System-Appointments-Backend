<?php
namespace App\Services\Evolution\Validations;

use App\Entity\Evolution\Evolution;
use App\Exception\Evolution\EvolutionAccessDeniedException;
use App\Exception\Evolution\EvolutionAlreadySignedException;
use App\Exception\Evolution\EvolutionNotFoundException;
use App\Services\Appointment\AppointmentFinderService;
use App\Services\Permissions\PermissionsService;

/**
 * Reglas para tocar una evolución existente (editarla o firmarla): es del paciente de la ruta,
 * su autor es el médico autenticado y sigue en borrador.
 */
final class EvolutionDraftValidatorService
{
    private AppointmentFinderService $appointmentFinder;
    private PermissionsService $permissionsService;

    public function __construct()
    {
        $this->appointmentFinder = new AppointmentFinderService();
        $this->permissionsService = new PermissionsService();
    }

    public function validate(Evolution $evolution, int $patientId, int $roleId, int $userId): void
    {
        if ($evolution->getPatientId() !== $patientId) {
            throw new EvolutionNotFoundException($evolution->getId());
        }

        $appointment = $this->appointmentFinder->find($evolution->getAppointmentId());

        if (!$this->permissionsService->canWriteEvolution($roleId, $userId, $appointment)
            || $appointment->getMedicalId() !== $evolution->getMedicalId()) {
            throw new EvolutionAccessDeniedException('Solo el autor de la evolución puede modificarla.');
        }

        if ($evolution->isSigned()) {
            throw new EvolutionAlreadySignedException($evolution->getId());
        }
    }
}

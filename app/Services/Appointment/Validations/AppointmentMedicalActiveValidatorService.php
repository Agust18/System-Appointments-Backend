<?php
namespace App\Services\Appointment\Validations;

use App\Entity\User\User;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;

/**
 * Regla compartida: el médico existe y su usuario está activo (único `is_active`;
 * `medicals` no tiene). Devuelve el usuario del médico para que el caller no lo
 * vuelva a buscar (nombre para los mensajes, comparación con el paciente).
 *
 * La usan el alta (panel y reserva pública), la edición y la disponibilidad.
 */
final class AppointmentMedicalActiveValidatorService
{
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
    }

    public function validate(int $medicalId): User
    {
        $medical = $this->medicalFinderService->find($medicalId);
        $user = $this->userFinderService->find($medical->getUserId());

        if (!$user->getIsActive()) {
            throw new MedicalNotAvailableException();
        }

        return $user;
    }
}

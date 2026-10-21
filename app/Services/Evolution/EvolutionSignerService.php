<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\EvolutionToEvolutionResponseConverter;
use App\Dto\Response\Evolution\EvolutionResponse;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Exception\Evolution\EvolutionAlreadySignedException;
use App\Exception\Evolution\EvolutionAppointmentNotEligibleException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Evolution\EvolutionModel;
use App\Services\Evolution\Validations\EvolutionDraftValidatorService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Firma una evolución: la cierra y, en la misma transacción, pasa el turno a `completed`.
 * El turno también se puede completar a mano (PUT /appointments); si ya está completado, la firma sigue valiendo.
 */
final class EvolutionSignerService
{
    private BaseConnection $db;
    private EvolutionModel $evolutionModel;
    private AppointmentModel $appointmentModel;
    private EvolutionFinderService $finder;
    private EvolutionToEvolutionResponseConverter $converter;
    private EvolutionDraftValidatorService $validator;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->evolutionModel = new EvolutionModel();
        $this->appointmentModel = new AppointmentModel();
        $this->finder = new EvolutionFinderService();
        $this->converter = new EvolutionToEvolutionResponseConverter();
        $this->validator = new EvolutionDraftValidatorService();
    }

    public function sign(int $patientId, int $id, int $roleId, int $userId): EvolutionResponse
    {
        $evolution = $this->finder->find($id);
        $this->validator->validate($evolution, $patientId, $roleId, $userId);

        try {
            $this->db->transBegin();

            $appointment = $this->appointmentModel->find($evolution->getAppointmentId());
            if (!$appointment) {
                throw new AppointmentNotFoundException($evolution->getAppointmentId());
            }
            // el turno pudo cancelarse entre que se guardó el borrador y la firma
            if (!$this->appointmentModel->markCompleted($appointment->getId()) && $appointment->getStatus() !== 'completed') {
                throw new EvolutionAppointmentNotEligibleException($appointment->getStatus());
            }

            // atómico: si otra firma se adelantó no se pisa signed_at
            if (!$this->evolutionModel->signIfDraft($id)) {
                throw new EvolutionAlreadySignedException($id);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return $this->converter->convert($this->finder->find($id));
    }
}

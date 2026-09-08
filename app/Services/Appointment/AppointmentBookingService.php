<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;
use Exception;

final class AppointmentBookingService
{
    private BaseConnection $db;
    private AppointmentModel $appointmentModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->appointmentModel = new AppointmentModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
    }

    public function book(AppointmentRequest $request): Appointment
    {
        $lockName = sprintf('appt_lock_%d_%s', $request->getMedicalId(), $request->getFecha());
        
        // 1. Mutex de aplicación a nivel base de datos (timeout 10s)
        $lockAcquired = $this->acquireLock($lockName, 10);
        if (!$lockAcquired) {
            throw new Exception('El sistema está ocupado procesando otra reserva para este profesional. Por favor, intente nuevamente en unos segundos.');
        }

        try {
            // Iniciar transacción dentro del lock
            $this->db->transBegin();

            // Recalcular duración y bloque total con servicio/override vigentes
            $service = $this->serviceModel->find($request->getServiceId());
            if (!$service || !$service->isActive()) {
                throw new Exception('El servicio seleccionado no está disponible.');
            }

            $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService(
                $request->getMedicalId(),
                $request->getServiceId()
            );
            if (!$medicalService) {
                throw new Exception('El profesional no ofrece este servicio actualmente.');
            }

            $effectiveDuration = ($medicalService->getCustomDurationMinutes() !== null)
                ? $medicalService->getCustomDurationMinutes()
                : $service->getDurationMinutes();

            $totalBlockMinutes = $effectiveDuration + $service->getBufferMinutes();

            // 4. Calcular horas de inicio y fin del turno
            $horaInicioFormatted = substr($request->getHora(), 0, 5) . ':00';
            $horaInicioDt = new DateTime("{$request->getFecha()} {$horaInicioFormatted}");
            $horaFinDt = (clone $horaInicioDt)->modify("+{$totalBlockMinutes} minutes");
            $horaFinFormatted = $horaFinDt->format('H:i:s');

            // 5. Verificación final de solapamiento (intersección contra turnos activos)
            $hasOverlap = $this->appointmentModel->hasOverlap(
                $request->getMedicalId(),
                $request->getFecha(),
                $horaInicioFormatted,
                $horaFinFormatted
            );

            if ($hasOverlap) {
                throw new Exception('El horario seleccionado ya no se encuentra disponible. Por favor, elija otro horario.');
            }

            // Instanciar e insertar entidad completa
            $appointment = new Appointment(
                null,
                $request->getMedicalId(),
                $request->getPatientId(),
                $request->getServiceId(),
                new DateTime($request->getFecha()),
                $horaInicioDt,
                $horaFinDt,
                $request->getNota(),
                $request->getStatus(),
                new DateTime(),
                new DateTime()
            );

            $createdAppointment = $this->appointmentModel->insert($appointment);

            // Commit
            $this->db->transCommit();

            return $createdAppointment;

        } catch (Exception $e) {
            // Rollback incondicional si la transacción quedó abierta
            $this->db->transRollback();
            throw $e;
        } finally {
            // 8. Liberar lock siempre, garantizado en el bloque finally
            $this->releaseLock($lockName);
        }
    }

    private function acquireLock(string $lockName, int $timeoutSeconds): bool
    {
        $result = $this->db->query("SELECT GET_LOCK(?, ?) as is_locked", [$lockName, $timeoutSeconds]);
        $row = $result->getRow();
        return $row && (int)$row->is_locked === 1;
    }

    private function releaseLock(string $lockName): void
    {
        $this->db->query("SELECT RELEASE_LOCK(?) as is_released", [$lockName]);
    }
}

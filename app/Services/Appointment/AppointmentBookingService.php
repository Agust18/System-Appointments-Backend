<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\BookingLockTimeoutException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
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
    private ServiceDurationCalculatorService $durationCalculator;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->appointmentModel = new AppointmentModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->durationCalculator = new ServiceDurationCalculatorService();
    }

    public function book(AppointmentRequest $request): Appointment
    {
        $lockName = sprintf('appt_lock_%d_%s', $request->getMedicalId(), $request->getFecha());
        
        // 1. Mutex de aplicación a nivel base de datos (timeout 10s)
        $lockAcquired = $this->acquireLock($lockName, 10);
        if (!$lockAcquired) {
            throw new BookingLockTimeoutException();
        }

        try {
            $this->db->transBegin();

            // recalcula duración y bloque total con servicio/override vigentes
            $service = $this->serviceModel->find($request->getServiceId());
            if (!$service || !$service->isActive()) {
                throw new ServiceNotAvailableException('El servicio seleccionado no está disponible.');
            }

            $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService(
                $request->getMedicalId(),
                $request->getServiceId()
            );
            if (!$medicalService) {
                throw new MedicalServiceNotOfferedException('El profesional no ofrece este servicio actualmente.');
            }

            $duration = $this->durationCalculator->calculate($service, $medicalService);
            $totalBlockMinutes = $duration->getTotalBlockMinutes();

            //calcular horas de inicio y fin del turno
            $horaInicioFormatted = substr($request->getHora(), 0, 5) . ':00';
            $horaInicioDt = new DateTime("{$request->getFecha()} {$horaInicioFormatted}");
            $horaFinDt = (clone $horaInicioDt)->modify("+{$totalBlockMinutes} minutes");
            $horaFinFormatted = $horaFinDt->format('H:i:s');

            //verificación final de solapamiento (intersección contra turnos activos)
            $hasOverlap = $this->appointmentModel->hasOverlap(
                $request->getMedicalId(),
                $request->getFecha(),
                $horaInicioFormatted,
                $horaFinFormatted
            );

            if ($hasOverlap) {
                throw new AppointmentAlreadyExistsException();
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
            $this->db->transCommit();
            return $createdAppointment;

        } catch (Exception $e) {
            //rollback incondicional si la transacción quedó abierta
            $this->db->transRollback();
            throw $e;
        } finally {
            //liberar lock siempre, garantizado en el bloque finally
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

<?php
namespace App\Services\Appointment;

use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\BookingLockTimeoutException;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\Validations\AppointmentServiceOfferValidatorService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

/**
 * Service delegado: ejecuta una escritura de turno de forma atómica.
 * Lock advisory por (médico, fecha) + transacción + validación de servicio,
 * cálculo de bloque, chequeo de solapamiento y persistencia (inyectada).
 * Lo comparten AppointmentBookingService (alta) y AppointmentUpdateBookingService (edición).
 */
final class AppointmentSlotLockService
{
    private const LOCK_TIMEOUT_SECONDS = 10;

    private BaseConnection $db;
    private AppointmentModel $appointmentModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private ServiceDurationCalculatorService $durationCalculator;
    private AppointmentServiceOfferValidatorService $serviceOfferValidator;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->appointmentModel = new AppointmentModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->durationCalculator = new ServiceDurationCalculatorService();
        $this->serviceOfferValidator = new AppointmentServiceOfferValidatorService();
    }

    /**
     * @param callable(DateTime $horaInicio, DateTime $horaFin): Appointment $persist
     *        Se ejecuta dentro del lock y la transacción, con el bloque ya validado.
     */
    public function run(
        int $medicalId,
        string $fecha,
        string $hora,
        int $serviceId,
        ?int $excludeAppointmentId,
        callable $persist
    ): Appointment {
        $lockName = sprintf('appt_lock_%d_%s', $medicalId, $fecha);

        // 1. Mutex de aplicación a nivel base de datos
        if (!$this->acquireLock($lockName, self::LOCK_TIMEOUT_SECONDS)) {
            throw new BookingLockTimeoutException();
        }

        try {
            $this->db->transBegin();

            // recalcula duración y bloque total con servicio/override vigentes
            $service = $this->serviceModel->find($serviceId);
            $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
            $this->serviceOfferValidator->validate($service, $medicalService);

            $totalBlockMinutes = $this->durationCalculator->calculate($service, $medicalService)->getTotalBlockMinutes();

            // calcular horas de inicio y fin del turno
            $horaInicioFormatted = substr($hora, 0, 5) . ':00';
            $horaInicioDt = new DateTime("{$fecha} {$horaInicioFormatted}");
            $horaFinDt = (clone $horaInicioDt)->modify("+{$totalBlockMinutes} minutes");

            // verificación final de solapamiento (intersección contra turnos activos)
            $hasOverlap = $this->appointmentModel->hasOverlap(
                $medicalId,
                $fecha,
                $horaInicioFormatted,
                $horaFinDt->format('H:i:s'),
                $excludeAppointmentId
            );
            if ($hasOverlap) {
                throw new AppointmentAlreadyExistsException();
            }

            $appointment = $persist($horaInicioDt, $horaFinDt);
            $this->db->transCommit();

            return $appointment;
        } catch (\Throwable $e) {
            // rollback incondicional si la transacción quedó abierta
            $this->db->transRollback();
            throw $e;
        } finally {
            // liberar lock siempre, garantizado en el bloque finally
            $this->releaseLock($lockName);
        }
    }

    private function acquireLock(string $lockName, int $timeoutSeconds): bool
    {
        $result = $this->db->query("SELECT GET_LOCK(?, ?) as is_locked", [$lockName, $timeoutSeconds]);
        $row = $result->getRow();
        return $row && (int) $row->is_locked === 1;
    }

    private function releaseLock(string $lockName): void
    {
        $this->db->query("SELECT RELEASE_LOCK(?) as is_released", [$lockName]);
    }
}

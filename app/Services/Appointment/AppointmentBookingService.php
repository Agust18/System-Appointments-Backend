<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\BookingLockTimeoutException;
use App\Models\Appointment\AppointmentModel;
use App\Services\DayOff\MedicalDaysOffService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;


final class AppointmentBookingService
{
    private const LOCK_TIMEOUT_SECONDS = 10;

    private BaseConnection $db;
    private AppointmentModel $appointmentModel;
    private ServiceBlockResolverService $blockResolver;
    private MedicalDaysOffService $daysOffService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->appointmentModel = new AppointmentModel();
        $this->blockResolver = new ServiceBlockResolverService();
        $this->daysOffService = new MedicalDaysOffService();
    }

    public function book(AppointmentRequest $request): Appointment
    {
        return $this->runLocked(
            $request->getMedicalId(),
            $request->getFecha(),
            $request->getHora(),
            $request->getServiceId(),
            null,
            function (DateTime $horaInicio, DateTime $horaFin) use ($request): Appointment {
                $appointment = new Appointment(
                    null,
                    $request->getMedicalId(),
                    $request->getPatientId(),
                    $request->getServiceId(),
                    new DateTime($request->getFecha()),
                    $horaInicio,
                    $horaFin,
                    $request->getNota(),
                    $request->getStatus(),
                    new DateTime(),
                    new DateTime()
                );

                return $this->appointmentModel->insert($appointment);
            }
        );
    }

    public function reschedule(Appointment $appointment, AppointmentUpdateRequest $request, int $id): Appointment
    {
        // Valores finales: los que vengan en el request pisan a los actuales.
        $finalMedicalId = $request->getMedicalId() ?? $appointment->getMedicalId();
        $finalServiceId = $request->getServiceId() ?? $appointment->getServiceId();
        $finalFecha = $request->getFecha() ?? $appointment->getFecha()->format('Y-m-d');
        $finalHora = $request->getHora() ?? $appointment->getHora()->format('H:i:s');

        // Recalcula bloque y verifica solapamiento excluyendo el propio turno que se edita.
        return $this->runLocked(
            $finalMedicalId,
            $finalFecha,
            $finalHora,
            $finalServiceId,
            $id,
            function (DateTime $horaInicio, DateTime $horaFin) use ($appointment, $request): Appointment {
                $appointment->modify($request);
                $appointment->setHoraFin($horaFin);

                return $this->appointmentModel->update($appointment);
            }
        );
    }

    /**
     * @param callable(DateTime $horaInicio, DateTime $horaFin): Appointment $persist
     *        Se ejecuta dentro del lock y la transacción, con el bloque ya validado.
     */
    private function runLocked(
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

            // barrera real: un día no laborable no se reserva aunque el selector no lo muestre
            $this->daysOffService->assertNotDayOff($medicalId, $fecha);

            // recalcula duración y bloque total con servicio/override vigentes
            $totalBlockMinutes = $this->blockResolver->resolve($medicalId, $serviceId)->getTotalBlockMinutes();

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

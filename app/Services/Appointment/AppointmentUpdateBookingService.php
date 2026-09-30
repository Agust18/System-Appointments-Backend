<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\AppointmentServiceRequiredException;
use App\Exception\Appointment\BookingLockTimeoutException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

final class AppointmentUpdateBookingService
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

    public function update(Appointment $appointment, AppointmentUpdateRequest $request, int $id): Appointment
    {
        // Valores finales: los que vengan en el request pisan a los actuales.
        $finalMedicalId = $request->getMedicalId() ?? $appointment->getMedicalId();
        $finalServiceId = $request->getServiceId() ?? $appointment->getServiceId();
        $finalFecha = $request->getFecha() ?? $appointment->getFecha()->format('Y-m-d');
        $finalHora = $request->getHora() ?? $appointment->getHora()->format('H:i:s');

        if ($finalServiceId === null) {
            throw new AppointmentServiceRequiredException();
        }

        $lockName = sprintf('appt_lock_%d_%s', $finalMedicalId, $finalFecha);

        $lockAcquired = $this->acquireLock($lockName, 10);
        if (!$lockAcquired) {
            throw new BookingLockTimeoutException();
        }

        try {
            $this->db->transBegin();

            // Recalcular duración/buffer con el servicio y override vigentes.
            $service = $this->serviceModel->find($finalServiceId);
            if (!$service || !$service->isActive()) {
                throw new ServiceNotAvailableException('El servicio seleccionado no está disponible.');
            }

            $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($finalMedicalId, $finalServiceId);
            if (!$medicalService) {
                throw new MedicalServiceNotOfferedException('El profesional no ofrece este servicio actualmente.');
            }

            $effectiveDuration = ($medicalService->getCustomDurationMinutes() !== null)
                ? $medicalService->getCustomDurationMinutes()
                : $service->getDurationMinutes();

            $totalBlockMinutes = $effectiveDuration + $service->getBufferMinutes();

            $horaInicioFormatted = substr($finalHora, 0, 5) . ':00';
            $horaInicioDt = new DateTime("{$finalFecha} {$horaInicioFormatted}");
            $horaFinDt = (clone $horaInicioDt)->modify("+{$totalBlockMinutes} minutes");
            $horaFinFormatted = $horaFinDt->format('H:i:s');

            // Verificación de solapamiento, excluyendo el propio turno que se edita.
            $hasOverlap = $this->appointmentModel->hasOverlap(
                $finalMedicalId,
                $finalFecha,
                $horaInicioFormatted,
                $horaFinFormatted,
                $id
            );

            if ($hasOverlap) {
                throw new AppointmentAlreadyExistsException;
            }

            $appointment->modify($request);
            $appointment->setHoraFin($horaFinDt);

            $updatedAppointment = $this->appointmentModel->update($appointment);

            $this->db->transCommit();

            return $updatedAppointment;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        } finally {
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

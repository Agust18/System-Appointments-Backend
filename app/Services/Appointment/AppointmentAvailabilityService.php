<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use Exception;

final class AppointmentAvailabilityService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->appointmentModel = new AppointmentModel();
    }

    /**
     * Genera la lista de slots disponibles para un médico, servicio y fecha dados.
     *
     * @return array<array{start_time: string, end_time: string, block_end_time: string}>
     */
    public function getAvailableSlots(AppointmentAvailabilityRequest $request): array
    {
        $medicalId = $request->getMedicalId();
        $serviceId = $request->getServiceId();
        $fecha = $request->getFecha();

        $this->validateFecha($fecha);

        // 1. Validar y obtener el servicio
        $service = $this->serviceModel->find($serviceId);
        if (!$service || !$service->isActive()) {
            throw new Exception('El servicio seleccionado no existe o no se encuentra activo.');
        }

        // 2. Validar y obtener la asignación del médico
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        if (!$medicalService) {
            throw new Exception('El profesional seleccionado no ofrece el servicio indicado.');
        }

        // 3. Calcular duración efectiva del servicio y bloque total (con buffer)
        $effectiveDuration = ($medicalService->getCustomDurationMinutes() !== null)
            ? $medicalService->getCustomDurationMinutes()
            : $service->getDurationMinutes();

        $bufferMinutes = $service->getBufferMinutes();
        $totalBlockMinutes = $effectiveDuration + $bufferMinutes;
        $slotStepMinutes = $service->getSlotStepMinutes() > 0 ? $service->getSlotStepMinutes() : 15;

        // 4. Obtener rangos activos de atención para el día de la semana
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            return [];
        }

        // 5. Traer turnos agendados del día (vía Model, no SQL directo acá)
        $existingAppointments = $this->appointmentModel->findActiveHoursByMedicalAndFecha($medicalId, $fecha);

        $availableSlots = [];

        // 6. Recorrer cada rango de atención
        foreach ($activeRanges as $range) {
            $rangeStartMinutes = $this->timeToMinutes(substr($range->getStartTime(), 0, 5));
            $rangeEndMinutes = $this->timeToMinutes(substr($range->getEndTime(), 0, 5));

            for ($candidateStart = $rangeStartMinutes; $candidateStart <= ($rangeEndMinutes - $totalBlockMinutes); $candidateStart += $slotStepMinutes) {
                $candidateEnd = $candidateStart + $totalBlockMinutes;       // Con buffer (bloque total)
                $visibleEnd = $candidateStart + $effectiveDuration;        // Sin buffer (visible para paciente)

                if ($candidateEnd > $rangeEndMinutes) {
                    continue;
                }

                $candidateStartStr = $this->minutesToTime($candidateStart) . ':00';
                $candidateEndStr = $this->minutesToTime($candidateEnd) . ':00';

                // Chequear solapamiento: candidate_start < turno.hora_fin AND candidate_end > turno.hora
                $overlaps = false;
                foreach ($existingAppointments as $appt) {
                    $apptHora = substr($appt->hora, 0, 8);
                    $apptHoraFin = substr($appt->hora_fin, 0, 8);

                    if ($candidateStartStr < $apptHoraFin && $candidateEndStr > $apptHora) {
                        $overlaps = true;
                        break;
                    }
                }

                if (!$overlaps) {
                    $availableSlots[] = [
                        'start_time'     => substr($candidateStartStr, 0, 5),
                        'end_time'       => $this->minutesToTime($visibleEnd),       // Duración real sin buffer
                        'block_end_time' => substr($candidateEndStr, 0, 5)          // Fin con buffer (bloqueo total)
                    ];
                }
            }
        }

        return $availableSlots;
    }

    private function validateFecha(string $fecha): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('El formato de fecha debe ser YYYY-MM-DD');
        }

        [$year, $month, $day] = explode('-', $fecha);
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            throw new Exception('La fecha proporcionada no es una fecha válida en el calendario');
        }
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return ((int)$hours * 60) + (int)$minutes;
    }

    private function minutesToTime(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
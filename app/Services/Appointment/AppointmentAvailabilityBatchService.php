<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\SlotsByDateToAvailabilityBatchResponseConverter;
use App\Dto\Request\Appointment\AppointmentAvailabilityBatchRequest;
use App\Dto\Response\Appointment\AppointmentAvailabilityBatchResponse;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\Appointment\Validations\AppointmentAvailabilityValidatorService;
use App\Services\Appointment\Validations\AppointmentMedicalActiveValidatorService;
use DateTime;
use Throwable;

final class AppointmentAvailabilityBatchService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private AppointmentModel $appointmentModel;
    private AppointmentAvailabilityValidatorService $validator;
    private AppointmentMedicalActiveValidatorService $medicalActiveValidator;
    private ServiceBlockResolverService $blockResolver;
    private SlotGeneratorService $slotGenerator;
    private SlotsByDateToAvailabilityBatchResponseConverter $converter;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->appointmentModel = new AppointmentModel();
        $this->validator = new AppointmentAvailabilityValidatorService();
        $this->medicalActiveValidator = new AppointmentMedicalActiveValidatorService();
        $this->blockResolver = new ServiceBlockResolverService();
        $this->slotGenerator = new SlotGeneratorService();
        $this->converter = new SlotsByDateToAvailabilityBatchResponseConverter();
    }

    public function getAvailableSlotsBatch(AppointmentAvailabilityBatchRequest $request): AppointmentAvailabilityBatchResponse
    {
        $medicalId = $request->getMedicalId();
        $serviceId = $request->getServiceId();
        $from = $request->getFrom();
        $to = $request->getTo();

        $this->validator->validateQuery($medicalId, $serviceId);
        $this->validator->validateRange($from, $to);
        $this->validator->validateExcludeAppointmentId($request->getRawExcludeAppointmentId());

        // médico con usuario inactivo no ofrece horarios (mismo criterio que la consulta de un día).
        $this->medicalActiveValidator->validate($medicalId);

        $duration = $this->blockResolver->resolve($medicalId, $serviceId);

        $dates = $this->buildDateRange($from, $to);
        $rangesByDay = $this->findRangesByDay($medicalId, $dates);
        $appointmentsByDate = $this->findAppointmentsByDate($medicalId, $from, $to, $request->getExcludeAppointmentId());

        $slotsByDate = [];
        foreach ($dates as $fecha) {
            $dayOfWeek = $this->dayOfWeek($fecha);
            if (empty($rangesByDay[$dayOfWeek])) {
                continue; // día no atendido: no aparece en el output
            }

            $dayAppointments = [];
            if (isset($appointmentsByDate[$fecha])) {
                $dayAppointments = $appointmentsByDate[$fecha];
            }

            $slotsByDate[$fecha] = $this->generateStartTimes($fecha, $rangesByDay[$dayOfWeek], $dayAppointments, $duration);
        }

        return $this->converter->convert($slotsByDate);
    }

    /**
     * Una sola query para todos los rangos horarios de los días de la semana involucrados.
     *
     * @param string[] $dates
     * @return array<int, \App\Entity\MedicalSchedule\MedicalSchedule[]> agrupados por day_of_week
     */
    private function findRangesByDay(int $medicalId, array $dates): array
    {
        $daysOfWeek = array_values(array_unique(array_map(
            fn (string $fecha): int => $this->dayOfWeek($fecha),
            $dates
        )));

        $rangesByDay = [];
        foreach ($this->medicalScheduleModel->findActiveRangesByMedicalAndDays($medicalId, $daysOfWeek) as $range) {
            $rangesByDay[$range->getDayOfWeek()][] = $range;
        }

        return $rangesByDay;
    }

    /**
     * Una sola query para todos los turnos activos del rango.
     *
     * @return array<string, array> agrupados por fecha
     */
    private function findAppointmentsByDate(int $medicalId, string $from, string $to, ?int $excludeAppointmentId): array
    {
        $appointmentsByDate = [];
        foreach ($this->appointmentModel->findActiveHoursByMedicalAndFechaRange($medicalId, $from, $to, $excludeAppointmentId) as $row) {
            $appointmentsByDate[$row->fecha][] = $row;
        }

        return $appointmentsByDate;
    }

    /**
     * Horas de inicio disponibles de un día. Si el generador falla, ese día queda
     * sin slots y se loguea, para no tirar abajo todo el rango.
     *
     * @return string[]
     */
    private function generateStartTimes(string $fecha, array $dayRanges, array $dayAppointments, ServiceDuration $duration): array
    {
        try {
            $slots = $this->slotGenerator->generate(
                $dayRanges,
                $dayAppointments,
                $duration->getEffectiveDurationMinutes(),
                $duration->getTotalBlockMinutes()
            );
        } catch (Throwable $e) {
            log_message('error', 'SlotGenerator falló para fecha {fecha}: {msg}', ['fecha' => $fecha, 'msg' => $e->getMessage()]);
            return [];
        }

        return array_map(static fn (array $slot): string => $slot['start_time'], $slots);
    }

    private function dayOfWeek(string $fecha): int
    {
        return (int) date('N', strtotime($fecha));
    }

    /**
     * @return string[] fechas Y-m-d entre $from y $to (inclusive)
     */
    private function buildDateRange(string $from, string $to): array
    {
        $dates = [];
        $current = new DateTime($from);
        $end = new DateTime($to);

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        return $dates;
    }
}

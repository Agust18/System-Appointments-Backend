<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\SlotsByDateToAvailabilityBatchResponseConverter;
use App\Dto\Request\Appointment\AppointmentAvailabilityBatchRequest;
use App\Dto\Response\Appointment\AppointmentAvailabilityBatchResponse;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\Validations\AppointmentAvailabilityQueryValidatorService;
use App\Services\Appointment\Validations\AppointmentAvailabilityRangeValidatorService;
use App\Services\Appointment\Validations\AppointmentServiceOfferValidatorService;
use DateTime;
use Throwable;

final class AppointmentAvailabilityBatchService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private AppointmentModel $appointmentModel;
    private AppointmentAvailabilityQueryValidatorService $queryValidator;
    private AppointmentAvailabilityRangeValidatorService $rangeValidator;
    private AppointmentServiceOfferValidatorService $serviceOfferValidator;
    private ServiceDurationCalculatorService $durationCalculator;
    private SlotGeneratorService $slotGenerator;
    private SlotsByDateToAvailabilityBatchResponseConverter $converter;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->appointmentModel = new AppointmentModel();
        $this->queryValidator = new AppointmentAvailabilityQueryValidatorService();
        $this->rangeValidator = new AppointmentAvailabilityRangeValidatorService();
        $this->serviceOfferValidator = new AppointmentServiceOfferValidatorService();
        $this->durationCalculator = new ServiceDurationCalculatorService();
        $this->slotGenerator = new SlotGeneratorService();
        $this->converter = new SlotsByDateToAvailabilityBatchResponseConverter();
    }

    public function getAvailableSlotsBatch(AppointmentAvailabilityBatchRequest $request): AppointmentAvailabilityBatchResponse
    {
        $this->queryValidator->validate($request->getMedicalId(), $request->getServiceId());
        $this->rangeValidator->validate($request->getFrom(), $request->getTo());

        $medicalId = $request->getMedicalId();
        $serviceId = $request->getServiceId();

        $service = $this->serviceModel->find($serviceId);
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        $this->serviceOfferValidator->validate($service, $medicalService);

        $duration = $this->durationCalculator->calculate($service, $medicalService);

        $dates = $this->buildDateRange($request->getFrom(), $request->getTo());
        $daysOfWeek = array_values(array_unique(array_map(
            static fn (string $fecha): int => (int) date('N', strtotime($fecha)),
            $dates
        )));

        // Query 1: todos los rangos horarios del rango, agrupados por day_of_week
        $rangesByDay = [];
        foreach ($this->medicalScheduleModel->findActiveRangesByMedicalAndDays($medicalId, $daysOfWeek) as $range) {
            $rangesByDay[$range->getDayOfWeek()][] = $range;
        }

        // Query 2: todos los turnos del rango, agrupados por fecha
        $appointmentsByDate = [];
        foreach ($this->appointmentModel->findActiveHoursByMedicalAndFechaRange($medicalId, $request->getFrom(), $request->getTo()) as $row) {
            $appointmentsByDate[$row->fecha][] = $row;
        }

        $slotsByDate = [];
        foreach ($dates as $fecha) {
            $dayOfWeek = (int) date('N', strtotime($fecha));
            if (empty($rangesByDay[$dayOfWeek])) {
                continue; // día no atendido: no aparece en el output
            }

            if ($this->isDayOff($medicalId, $fecha)) {
                continue; // hook para excepciones puntuales (feriados, licencias)
            }

            try {
                $slots = $this->slotGenerator->generate(
                    $rangesByDay[$dayOfWeek],
                    $appointmentsByDate[$fecha] ?? [],
                    $duration->getEffectiveDurationMinutes(),
                    $duration->getTotalBlockMinutes()
                );
                $slotsByDate[$fecha] = array_map(static fn (array $slot): string => $slot['start_time'], $slots);
            } catch (Throwable $e) {
                log_message('error', 'SlotGenerator falló para fecha {fecha}: {msg}', ['fecha' => $fecha, 'msg' => $e->getMessage()]);
                $slotsByDate[$fecha] = [];
            }
        }

        return $this->converter->convert($slotsByDate);
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

    /**
     * Hook para excepciones puntuales de agenda (feriados, licencias, día
     * bloqueado ad-hoc) que un profesional podrá marcar a futuro. Hoy no hay
     * tabla ni feature para esto — devuelve false siempre. Cuando se
     * implemente esa feature, este método consulta esa tabla; no requiere
     * tocar el resto de AppointmentAvailabilityBatchService.
     */
    private function isDayOff(int $medicalId, string $fecha): bool
    {
        return false;
    }
}

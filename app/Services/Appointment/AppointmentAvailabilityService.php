<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Exception\Appointment\InvalidAppointmentDateException;
use App\Exception\Appointment\InvalidAppointmentDateFormatException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\Validations\AppointmentAvailabilityQueryValidatorService;

final class AppointmentAvailabilityService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private AppointmentModel $appointmentModel;
    private AppointmentAvailabilityQueryValidatorService $queryValidator;
    private ServiceDurationCalculatorService $durationCalculator;
    private SlotGeneratorService $slotGenerator;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->appointmentModel = new AppointmentModel();
        $this->queryValidator = new AppointmentAvailabilityQueryValidatorService();
        $this->durationCalculator = new ServiceDurationCalculatorService();
        $this->slotGenerator = new SlotGeneratorService();
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

        $this->queryValidator->validate($medicalId, $serviceId);
        $this->validateFecha($fecha);

        //valida y obtener el servicio
        $service = $this->serviceModel->find($serviceId);
        if (!$service || !$service->isActive()) {
            throw new ServiceNotAvailableException();
        }

        //valida y obtener la asignación del médico
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        if (!$medicalService) {
            throw new MedicalServiceNotOfferedException('El profesional seleccionado no ofrece el servicio indicado.');
        }

        //calcula duración efectiva del servicio y bloque total (con buffer)
        $duration = $this->durationCalculator->calculate($service, $medicalService);
        $slotStepMinutes = $service->getSlotStepMinutes() > 0 ? $service->getSlotStepMinutes() : 15;

        //obtiene rangos activos de atención para el día de la semana
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            return [];
        }

        //trae turnos agendados del día (vía Model, no SQL directo acá)
        $existingAppointments = $this->appointmentModel->findActiveHoursByMedicalAndFecha($medicalId, $fecha);

        return $this->slotGenerator->generate(
            $activeRanges,
            $existingAppointments,
            $duration->getEffectiveDurationMinutes(),
            $duration->getTotalBlockMinutes(),
            $slotStepMinutes
        );
    }

    private function validateFecha(string $fecha): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new InvalidAppointmentDateFormatException();
        }

        [$year, $month, $day] = explode('-', $fecha);
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            throw new InvalidAppointmentDateException();
        }
    }
}
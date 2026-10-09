<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\Validations\AppointmentAvailabilityDateValidatorService;
use App\Services\Appointment\Validations\AppointmentAvailabilityQueryValidatorService;
use App\Services\Appointment\Validations\AppointmentServiceOfferValidatorService;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;

final class AppointmentAvailabilityService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private AppointmentModel $appointmentModel;
    private AppointmentAvailabilityQueryValidatorService $queryValidator;
    private AppointmentAvailabilityDateValidatorService $dateValidator;
    private AppointmentServiceOfferValidatorService $serviceOfferValidator;
    private ServiceDurationCalculatorService $durationCalculator;
    private SlotGeneratorService $slotGenerator;
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->appointmentModel = new AppointmentModel();
        $this->queryValidator = new AppointmentAvailabilityQueryValidatorService();
        $this->dateValidator = new AppointmentAvailabilityDateValidatorService();
        $this->serviceOfferValidator = new AppointmentServiceOfferValidatorService();
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
        $this->dateValidator->validate($fecha);

        //médico con usuario inactivo no ofrece horarios (mismo criterio que el alta del turno).
        $medical = $this->medicalFinderService->find($medicalId);
        if (!$this->userFinderService->find($medical->getUserId())->getIsActive()) {
            throw new MedicalNotAvailableException();
        }

        //obtiene el servicio y la asignación del médico, y valida que estén disponibles
        $service = $this->serviceModel->find($serviceId);
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        $this->serviceOfferValidator->validate($service, $medicalService);

        //calcula duración efectiva del servicio y bloque total
        $duration = $this->durationCalculator->calculate($service, $medicalService);

        //obtiene rangos activos de atención para el día de la semana
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            return [];
        }

        $existingAppointments = $this->appointmentModel->findActiveHoursByMedicalAndFecha($medicalId, $fecha);

        return $this->slotGenerator->generate(
            $activeRanges,
            $existingAppointments,
            $duration->getEffectiveDurationMinutes(),
            $duration->getTotalBlockMinutes()
        );
    }
}

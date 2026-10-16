<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Models\Appointment\AppointmentModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\Appointment\Validations\AppointmentAvailabilityValidatorService;
use App\Services\Appointment\Validations\AppointmentMedicalActiveValidatorService;

final class AppointmentAvailabilityService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private AppointmentModel $appointmentModel;
    private AppointmentAvailabilityValidatorService $validator;
    private AppointmentMedicalActiveValidatorService $medicalActiveValidator;
    private ServiceBlockResolverService $blockResolver;
    private SlotGeneratorService $slotGenerator;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->appointmentModel = new AppointmentModel();
        $this->validator = new AppointmentAvailabilityValidatorService();
        $this->medicalActiveValidator = new AppointmentMedicalActiveValidatorService();
        $this->blockResolver = new ServiceBlockResolverService();
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

        $this->validator->validateQuery($medicalId, $serviceId);
        $this->validator->validateDate($fecha);
        $this->validator->validateExcludeAppointmentId($request->getRawExcludeAppointmentId());

        //médico con usuario inactivo no ofrece horarios (mismo criterio que el alta del turno).
        $this->medicalActiveValidator->validate($medicalId);

        //valida que el servicio esté disponible y calcula duración efectiva y bloque total
        $duration = $this->blockResolver->resolve($medicalId, $serviceId);

        //obtiene rangos activos de atención para el día de la semana
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            return [];
        }

        $existingAppointments = $this->appointmentModel->findActiveHoursByMedicalAndFecha($medicalId, $fecha, $request->getExcludeAppointmentId());

        return $this->slotGenerator->generate(
            $activeRanges,
            $existingAppointments,
            $duration->getEffectiveDurationMinutes(),
            $duration->getTotalBlockMinutes()
        );
    }
}

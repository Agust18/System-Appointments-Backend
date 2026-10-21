<?php
namespace App\Services\DayOff;

use App\Converter\DayOff\Projection\AffectedAppointmentProjectionToAffectedAppointmentResponseConverter;
use App\Dto\Response\DayOff\AffectedAppointmentResponse;
use App\Entity\DayOff\DayOff;
use App\Models\Appointment\AppointmentModel;

final class AffectedAppointmentsFinderService
{
    private AppointmentModel $appointmentModel;
    private AffectedAppointmentProjectionToAffectedAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AffectedAppointmentProjectionToAffectedAppointmentResponseConverter();
    }

    /**
     * Turnos pending/confirmed dentro del bloqueo (de su médico, o de toda la clínica).
     *
     * @return AffectedAppointmentResponse[]
     */
    public function find(DayOff $dayOff): array
    {
        $projections = $this->appointmentModel->findActiveByDateRange(
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo()
        );

        return array_map(fn ($projection): AffectedAppointmentResponse => $this->converter->convert($projection), $projections);
    }
}

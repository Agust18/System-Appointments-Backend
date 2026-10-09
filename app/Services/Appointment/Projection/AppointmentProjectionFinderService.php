<?php

namespace App\Services\Appointment\Projection;

use App\Converter\Appointment\Projection\AppointmentProjectionToAppointmentProjectionResponseConverter;
use App\Dto\Response\Appointment\Projection\AppointmentProjectionResponse;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;

final class AppointmentProjectionFinderService
{
    private AppointmentModel $appointmentModel;
    private AppointmentProjectionToAppointmentProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentProjectionToAppointmentProjectionResponseConverter();
    }

    public function find(int $id): AppointmentProjection
    {
        $appointment = $this->appointmentModel->findProjection($id);

        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }
        return $appointment;
    }

    public function findResponse(int $id): AppointmentProjectionResponse
    {
        $appointment = $this->find($id);
        return $this->converter->convert($appointment);
    }
}

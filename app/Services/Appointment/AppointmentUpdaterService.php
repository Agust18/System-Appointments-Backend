<?php

namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;

final class AppointmentUpdaterService
{
    private AppointmentModel $appointmentModel;
    private AppointmentToAppointmentResponseConverter $converter;
    private AppointmentUpdateValidatorService $validator;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->validator = new AppointmentUpdateValidatorService();
    }

    public function update(
        AppointmentUpdateRequest $request,
        int $id
    ): AppointmentResponse {

        $appointment = $this->validator->validate($request, $id);

        $appointment->modify($request);

        $updatedAppointment = $this->appointmentModel->update($appointment);

        return $this->converter->convert($updatedAppointment);
    }
}
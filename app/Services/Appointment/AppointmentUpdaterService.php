<?php

namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;

final class AppointmentUpdaterService
{
    private AppointmentModel $appointmentModel;
    private AppointmentToAppointmentResponseConverter $converter;
    private AppointmentUpdateValidatorService $validator;

    private AppointmentUpdateBookingService $book;
    private AppointmentFinderService $appointmentFinderService;
    private AppointmentNotificationService $notificationService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->validator = new AppointmentUpdateValidatorService();
        $this->appointmentFinderService = new AppointmentFinderService();
        $this->book = new AppointmentUpdateBookingService();
        $this->notificationService = new AppointmentNotificationService();
    }

    public function update(AppointmentUpdateRequest $request, int $id): AppointmentResponse
    {
        $appointment = $this->appointmentFinderService->find($id);

        //validaciones de negocio (roles, fecha pasada, etc.) — sin lock
        $this->validator->validate($request, $appointment, $id);

        //reserva atómica: lock + transacción + recálculo + solapamiento + modify + update
        $updatedAppointment = $this->book->update($appointment, $request, $id);

        $this->notificationService->notifyOnUpdate($appointment, $request, $id);

        return $this->converter->convert($updatedAppointment);
    }
}
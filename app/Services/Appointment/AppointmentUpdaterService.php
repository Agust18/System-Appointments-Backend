<?php

namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Services\Appointment\Notification\AppointmentUpdateNotificationService;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;

final class AppointmentUpdaterService
{
  
    private AppointmentToAppointmentResponseConverter $converter;
    private AppointmentUpdateValidatorService $validator;

    private AppointmentBookingService $bookingService;
    private AppointmentFinderService $appointmentFinderService;
    private AppointmentUpdateNotificationService $notificationService;

    public function __construct()
    {
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->validator = new AppointmentUpdateValidatorService();
        $this->appointmentFinderService = new AppointmentFinderService();
        $this->bookingService = new AppointmentBookingService();
        $this->notificationService = new AppointmentUpdateNotificationService();
    }

    public function update(AppointmentUpdateRequest $request, int $id): AppointmentResponse
    {
        $previousAppointment = $this->appointmentFinderService->find($id);
        
        $appointment = $this->appointmentFinderService->find($id);

        $this->validator->validate($request, $appointment, $id);
        
        $updatedAppointment = $this->bookingService->reschedule($appointment, $request, $id);

        $this->notificationService->notify($previousAppointment, $request, $id);

        return $this->converter->convert($updatedAppointment);
    }
}
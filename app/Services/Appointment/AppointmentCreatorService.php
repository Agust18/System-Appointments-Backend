<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Services\Appointment\Notification\AppointmentConfirmedNotificationService;
use App\Services\Appointment\Validations\AppointmentCreateValidatorService;


final class AppointmentCreatorService
{
    private AppointmentCreateValidatorService $validator;
    private AppointmentBookingService $bookingService;
    private AppointmentToAppointmentResponseConverter $converter;
    private AppointmentConfirmedNotificationService $notificationService;


    public function __construct()
    {
        $this->validator = new AppointmentCreateValidatorService();
        $this->bookingService = new AppointmentBookingService();
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->notificationService = new AppointmentConfirmedNotificationService();
    }

    public function create(AppointmentRequest $request): AppointmentResponse
    {
       
        $this->validator->validate($request);

        $newAppointment = $this->bookingService->book($request);

        $this->notificationService->notify($newAppointment->getId());

        return $this->converter->convert($newAppointment);
    }
}
<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentCreatedResponseConverter;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Response\Appointment\AppointmentCreatedResponse;
use App\Services\Appointment\Notification\AppointmentConfirmedNotificationService;
use App\Services\Appointment\Validations\AppointmentCreateValidatorService;

final class AppointmentCreatorService
{
    private AppointmentCreateValidatorService $validator;
    private AppointmentBookingService $bookingService;
    private AppointmentAccessService $accessService;
    private AppointmentToAppointmentCreatedResponseConverter $converter;
    private AppointmentConfirmedNotificationService $notificationService;

    public function __construct()
    {
        $this->validator = new AppointmentCreateValidatorService();
        $this->bookingService = new AppointmentBookingService();
        $this->accessService = new AppointmentAccessService();
        $this->converter = new AppointmentToAppointmentCreatedResponseConverter();
        $this->notificationService = new AppointmentConfirmedNotificationService();
    }

    public function create(AppointmentRequest $request): AppointmentCreatedResponse
    {
        $this->validator->validate($request);

        $newAppointment = $this->bookingService->book($request);

        $access = $this->accessService->issue($newAppointment->getId());

        $this->notificationService->notify($newAppointment->getId(), $access);

        return $this->converter->convert($newAppointment, $access);
    }
}
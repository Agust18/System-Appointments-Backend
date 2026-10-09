<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\Validations\AppointmentCreateValidatorService;


final class AppointmentCreatorService
{
    private AppointmentCreateValidatorService $validator;
    private AppointmentBookingService $bookingService;
    private AppointmentToAppointmentResponseConverter $converter;
    private AppointmentNotificationService $notificationService;


    public function __construct()
    {
        $this->validator = new AppointmentCreateValidatorService();
        $this->bookingService = new AppointmentBookingService();
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->notificationService = new AppointmentNotificationService();
    }

    public function create(AppointmentRequest $request): AppointmentResponse
    {
        // Validaciones de negocio sin concurrencia (roles, paciente, día, rango horario, servicio activo)
        $this->validator->validate($request);

        // Reserva atómica: GET_LOCK + recálculo de bloque + hasOverlap + insert + transacción
        $newAppointment = $this->bookingService->book($request);

        $this->notificationService->notifyOnCreate($newAppointment->getId());

        return $this->converter->convert($newAppointment);
    }
}
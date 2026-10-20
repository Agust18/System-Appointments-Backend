<?php
namespace App\Services\Appointment;

use App\Dto\Response\Appointment\AppointmentAccessCodeResponse;
use App\Exception\Appointment\AppointmentNotModifiableException;
use App\Services\Appointment\Notification\AppointmentAccessCodeNotificationService;

/**
 * Recepción/admin genera un código nuevo para un turno (el paciente lo perdió o no tiene email).
 * El anterior y el token del link dejan de servir. Si el paciente tiene email se le reenvía el acceso.
 */
final class AppointmentAccessCodeRegeneratorService
{
    private AppointmentFinderService $finder;
    private AppointmentAccessService $accessService;
    private AppointmentAccessCodeNotificationService $notificationService;

    public function __construct()
    {
        $this->finder = new AppointmentFinderService();
        $this->accessService = new AppointmentAccessService();
        $this->notificationService = new AppointmentAccessCodeNotificationService();
    }

    public function regenerate(int $appointmentId): AppointmentAccessCodeResponse
    {
        $appointment = $this->finder->find($appointmentId);

        if (!in_array($appointment->getStatus(), ['pending', 'confirmed'], true)) {
            throw new AppointmentNotModifiableException();
        }

        $access = $this->accessService->issue($appointmentId);

        $this->notificationService->notify($appointmentId, $access);

        return new AppointmentAccessCodeResponse($access->code);
    }
}

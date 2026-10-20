<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\AppointmentAccessCodeEmailTemplate;
use App\Entity\Appointment\AppointmentAccess;
use App\Models\Appointment\AppointmentModel;

/**
 * Recepción regeneró el código de un turno: si el paciente tiene email, se le reenvía el acceso nuevo.
 */
final class AppointmentAccessCodeNotificationService
{
    private AppointmentModel $appointmentModel;
    private AppointmentAccessCodeEmailTemplate $template;
    private AppointmentAccessLinkService $linkService;
    private AppointmentEmailDispatcherService $dispatcher;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->template         = new AppointmentAccessCodeEmailTemplate();
        $this->linkService      = new AppointmentAccessLinkService();
        $this->dispatcher       = new AppointmentEmailDispatcherService();
    }

    public function notify(int $appointmentId, AppointmentAccess $access): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null) {
            return;
        }

        $this->dispatcher->send(
            $projection->getEmail(),
            'Nuevo código para tu turno',
            $this->template->render($projection, $this->linkService->url($access->token), $access->code)
        );
    }
}

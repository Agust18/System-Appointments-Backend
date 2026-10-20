<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\ConfirmedEmailTemplate;
use App\Models\Appointment\AppointmentModel;

/**
 * "Tu turno fue confirmado": al crear un turno que nace confirmado o al
 * confirmar uno existente.
 */
final class AppointmentConfirmedNotificationService
{
    private AppointmentModel $appointmentModel;
    private ConfirmedEmailTemplate $template;
    private AppointmentEmailDispatcherService $dispatcher;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->template         = new ConfirmedEmailTemplate();
        $this->dispatcher       = new AppointmentEmailDispatcherService();
    }

    public function notify(int $appointmentId): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $this->dispatcher->send(
            $projection->getEmail(),
            'Tu turno fue confirmado',
            $this->template->render($projection)
        );
    }
}

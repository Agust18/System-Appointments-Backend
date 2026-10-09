<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\CancelledEmailTemplate;
use App\Models\Appointment\AppointmentModel;

final class AppointmentCancelledNotificationService
{
    private AppointmentModel $appointmentModel;
    private CancelledEmailTemplate $template;
    private AppointmentEmailDispatcherService $dispatcher;

    public function __construct(?callable $sender = null)
    {
        $this->appointmentModel = new AppointmentModel();
        $this->template         = new CancelledEmailTemplate();
        $this->dispatcher       = new AppointmentEmailDispatcherService($sender);
    }

    public function notify(int $appointmentId): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null) {
            return;
        }

        $this->dispatcher->send(
            $projection->getEmail(),
            'Tu turno fue cancelado',
            $this->template->render($projection)
        );
    }
}

<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\RescheduledEmailTemplate;
use App\Models\Appointment\AppointmentModel;

final class AppointmentRescheduledNotificationService
{
    private AppointmentModel $appointmentModel;
    private RescheduledEmailTemplate $template;
    private AppointmentEmailDispatcherService $dispatcher;

    public function __construct(?callable $sender = null)
    {
        $this->appointmentModel = new AppointmentModel();
        $this->template         = new RescheduledEmailTemplate();
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
            'Tu turno fue reprogramado',
            $this->template->render($projection)
        );
    }
}

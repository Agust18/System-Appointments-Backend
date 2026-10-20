<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\ConfirmedEmailTemplate;
use App\Entity\Appointment\AppointmentAccess;
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
    private AppointmentAccessLinkService $linkService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->template         = new ConfirmedEmailTemplate();
        $this->dispatcher       = new AppointmentEmailDispatcherService();
        $this->linkService      = new AppointmentAccessLinkService();
    }

    /**
     * $access: credenciales recién emitidas (solo al crear el turno); si viene, el mail incluye link y código.
     */
    public function notify(int $appointmentId, ?AppointmentAccess $access = null): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $this->dispatcher->send(
            $projection->getEmail(),
            'Tu turno fue confirmado',
            $this->template->render(
                $projection,
                $access ? $this->linkService->url($access->token) : null,
                $access?->code
            )
        );
    }
}

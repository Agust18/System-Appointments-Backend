<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\ConfirmedEmailTemplate;
use App\Email\Appointment\ReceptionNewAppointmentEmailTemplate;
use App\Entity\Appointment\AppointmentAccess;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Models\Appointment\AppointmentModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;

/**
 * Turno nacido confirmado por reserva pública: avisa al paciente (asunto propio)
 * y a recepción. Cada envío es independiente: si falla uno, el otro sale igual.
 */
final class PublicAppointmentCreatedNotificationService
{
    private AppointmentModel $appointmentModel;
    private ConfirmedEmailTemplate $patientTemplate;
    private ReceptionNewAppointmentEmailTemplate $receptionTemplate;
    private TenantEmailSettingsResolverService $settingsResolver;
    private AppointmentEmailDispatcherService $dispatcher;
    private AppointmentAccessLinkService $linkService;

    public function __construct()
    {
        $this->appointmentModel  = new AppointmentModel();
        $this->patientTemplate   = new ConfirmedEmailTemplate();
        $this->receptionTemplate = new ReceptionNewAppointmentEmailTemplate();
        $this->settingsResolver  = new TenantEmailSettingsResolverService();
        $this->dispatcher        = new AppointmentEmailDispatcherService();
        $this->linkService       = new AppointmentAccessLinkService();
    }

    public function notify(int $appointmentId, AppointmentAccess $access): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $this->dispatcher->send(
            $projection->getEmail(),
            'Tu turno fue creado y confirmado',
            $this->patientTemplate->render($projection, $this->linkService->url($access->token), $access->code)
        );

        $this->notifyReception($projection);
    }

    private function notifyReception(AppointmentProjection $projection): void
    {
        if (!$this->settingsResolver->isReceptionEmailEnabled()) {
            return;
        }

        $receptionEmail = $this->settingsResolver->receptionEmail();
        if ($receptionEmail === null) {
            log_message('warning', 'reception.email no está configurado: no se avisó a recepción del turno ' . $projection->getId() . '.');
            return;
        }

        $this->dispatcher->send(
            $receptionEmail,
            'Nuevo turno agendado',
            $this->receptionTemplate->render($projection)
        );
    }
}

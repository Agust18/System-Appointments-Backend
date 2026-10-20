<?php

namespace App\Services\Appointment\Notification;

use App\Email\Appointment\ReceptionCancelledAppointmentEmailTemplate;
use App\Models\Appointment\AppointmentModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;

/**
 * Turno cancelado por el propio paciente desde `/consulta`: avisa al paciente ("Tu turno fue cancelado")
 * y a recepción, porque se liberó un horario. Cada envío es independiente.
 */
final class PublicAppointmentCancelledNotificationService
{
    private AppointmentModel $appointmentModel;
    private AppointmentCancelledNotificationService $patientNotification;
    private ReceptionCancelledAppointmentEmailTemplate $receptionTemplate;
    private TenantEmailSettingsResolverService $settingsResolver;
    private AppointmentEmailDispatcherService $dispatcher;

    public function __construct()
    {
        $this->appointmentModel    = new AppointmentModel();
        $this->patientNotification = new AppointmentCancelledNotificationService();
        $this->receptionTemplate   = new ReceptionCancelledAppointmentEmailTemplate();
        $this->settingsResolver    = new TenantEmailSettingsResolverService();
        $this->dispatcher          = new AppointmentEmailDispatcherService();
    }

    public function notify(int $appointmentId): void
    {
        $this->patientNotification->notify($appointmentId);

        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || !$this->settingsResolver->isReceptionEmailEnabled()) {
            return;
        }

        $receptionEmail = $this->settingsResolver->receptionEmail();
        if ($receptionEmail === null) {
            log_message('warning', 'reception.email no está configurado: no se avisó a recepción de la cancelación del turno ' . $appointmentId . '.');
            return;
        }

        $this->dispatcher->send(
            $receptionEmail,
            'Turno cancelado por el paciente',
            $this->receptionTemplate->render($projection)
        );
    }
}

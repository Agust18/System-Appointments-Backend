<?php

namespace App\Services\Appointment\Notification;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class AppointmentEmailDispatcherService
{
    private TenantEmailSettingsResolverService $settingsResolver;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->emailSender      = new EmailSenderService();
    }

    /**
     * $to null: paciente cargado sin email por recepción. No se envía nada; recepción le avisa por teléfono.
     */
    public function send(?string $to, string $subject, string $body): void
    {
        if ($to === null) {
            return;
        }

        $request = new EmailRequest(
            to: $to,
            subject: $subject,
            body: $body,
            replyTo: $this->settingsResolver->replyTo(),
        );

        try {
            $this->emailSender->send($request);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar el email de notificación de turno: ' . $e->getMessage());
        }
    }
}

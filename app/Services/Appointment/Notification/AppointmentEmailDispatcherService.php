<?php

namespace App\Services\Appointment\Notification;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Services\Tenant\TenantEmailSettingsResolverService;

/**
 * Envío de los avisos de turnos: agrega el reply-to del Tenant y nunca corta
 * el flujo. El turno ya fue confirmado en DB antes de llegar acá: si el email
 * falla, se loguea y la respuesta al cliente sigue su curso.
 */
final class AppointmentEmailDispatcherService
{
    private TenantEmailSettingsResolverService $settingsResolver;
    /** @var callable(EmailRequest): mixed */
    private $sender;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->sender           = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
    }

    public function send(string $to, string $subject, string $body): void
    {
        $request = new EmailRequest(to: $to, subject: $subject, body: $body, replyTo: $this->settingsResolver->replyTo());

        try {
            ($this->sender)($request);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar el email de notificación de turno: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Request\Email\EmailRequest;
use App\Email\Appointment\AppointmentEmailTemplateRenderer;
use App\Email\EmailSenderService;
use App\Entity\Appointment\Appointment;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Models\Appointment\AppointmentModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class AppointmentNotificationService
{
    private AppointmentModel $appointmentModel;
    private AppointmentEmailTemplateRenderer $renderer;
    private TenantEmailSettingsResolverService $settingsResolver;
    /** @var callable(EmailRequest): mixed */
    private $sender;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->appointmentModel = new AppointmentModel();
        $this->renderer         = new AppointmentEmailTemplateRenderer();
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->sender          = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
    }

    public function notifyOnCreate(int $appointmentId): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $body = $this->renderer->renderConfirmedBody($projection);
        $this->sendSafely($this->buildRequest(
            to:      $projection->getEmail(),
            subject: 'Tu turno fue confirmado',
            body:    $body,
        ));
    }

    /**
     * Turno nacido confirmado por reserva pública: avisa al paciente (asunto propio)
     * y a recepción. Cada envío es independiente: si falla uno, el otro sale igual.
     */
    public function notifyOnPublicCreate(int $appointmentId): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $this->sendSafely($this->buildRequest(
            to:      $projection->getEmail(),
            subject: 'Tu turno fue creado y confirmado',
            body:    $this->renderer->renderConfirmedBody($projection),
        ));

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

        $this->sendSafely($this->buildRequest(
            to:      $receptionEmail,
            subject: 'Nuevo turno agendado',
            body:    $this->renderer->renderReceptionNewAppointmentBody($projection),
        ));
    }

    public function notifyOnUpdate(Appointment $before, AppointmentUpdateRequest $request, int $appointmentId): void
    {
        $newStatus  = $request->getStatus();
        $prevStatus = $before->getStatus();

        if ($newStatus === 'cancelled' && $prevStatus !== 'cancelled') {
            $projection = $this->appointmentModel->findProjection($appointmentId);
            if ($projection === null) {
                return;
            }
            $body = $this->renderer->renderCancelledBody($projection);
            $this->sendSafely($this->buildRequest(
                to:      $projection->getEmail(),
                subject: 'Tu turno fue cancelado',
                body:    $body,
            ));
            return;
        }

        if ($newStatus === 'confirmed' && $prevStatus !== 'confirmed') {
            $projection = $this->appointmentModel->findProjection($appointmentId);
            if ($projection === null) {
                return;
            }
            $body = $this->renderer->renderConfirmedBody($projection);
            $this->sendSafely($this->buildRequest(
                to:      $projection->getEmail(),
                subject: 'Tu turno fue confirmado',
                body:    $body,
            ));
            return;
        }

        $wasRescheduled = ($request->getFecha()    !== null && $request->getFecha()    !== $before->getFecha()->format('Y-m-d'))
                       || ($request->getHora()     !== null && $request->getHora()     !== $before->getHora()->format('H:i:s'))
                       || ($request->getMedicalId() !== null && $request->getMedicalId() !== $before->getMedicalId());

        if ($wasRescheduled) {
            $projection = $this->appointmentModel->findProjection($appointmentId);
            if ($projection === null) {
                return;
            }
            $body = $this->renderer->renderRescheduledBody($projection);
            $this->sendSafely($this->buildRequest(
                to:      $projection->getEmail(),
                subject: 'Tu turno fue reprogramado',
                body:    $body,
            ));
        }
    }

    private function buildRequest(string $to, string $subject, string $body): EmailRequest
    {
        return new EmailRequest(to: $to, subject: $subject, body: $body, replyTo: $this->settingsResolver->replyTo());
    }

    /**
     * El turno ya fue confirmado en DB antes de llegar acá: si el email
     * falla, se loguea pero nunca se corta la respuesta al cliente.
     */
    private function sendSafely(EmailRequest $request): void
    {
        try {
            ($this->sender)($request);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar el email de notificación de turno: ' . $e->getMessage());
        }
    }
}

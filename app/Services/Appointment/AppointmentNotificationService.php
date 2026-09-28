<?php

namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Request\Email\EmailRequest;
use App\Email\Appointment\AppointmentEmailTemplateRenderer;
use App\Email\EmailSenderService;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;

final class AppointmentNotificationService
{
    private AppointmentModel $appointmentModel;
    private AppointmentEmailTemplateRenderer $renderer;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->renderer         = new AppointmentEmailTemplateRenderer();
        $this->emailSender      = new EmailSenderService();
    }

    public function notifyOnCreate(int $appointmentId): void
    {
        $projection = $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null || $projection->getStatus() !== 'confirmed') {
            return;
        }

        $body = $this->renderer->renderConfirmedBody($projection);
        $this->sendSafely(new EmailRequest(
            to:      $projection->getEmail(),
            subject: 'Tu turno fue confirmado',
            body:    $body,
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
            $this->sendSafely(new EmailRequest(
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
            $this->sendSafely(new EmailRequest(
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
            $this->sendSafely(new EmailRequest(
                to:      $projection->getEmail(),
                subject: 'Tu turno fue reprogramado',
                body:    $body,
            ));
        }
    }

    /**
     * El turno ya fue confirmado en DB antes de llegar acá: si el email
     * falla, se loguea pero nunca se corta la respuesta al cliente.
     */
    private function sendSafely(EmailRequest $request): void
    {
        try {
            $this->emailSender->send($request);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo enviar el email de notificación de turno: ' . $e->getMessage());
        }
    }
}

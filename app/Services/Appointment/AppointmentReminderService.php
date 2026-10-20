<?php

namespace App\Services\Appointment;

use App\Dto\Request\Email\EmailRequest;
use App\Email\Appointment\ReminderEmailTemplate;
use App\Email\EmailSenderService;
use App\Entity\Tenant\Tenant;
use App\Models\Appointment\AppointmentModel;
use App\Models\Tenant\TenantModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use App\Tenancy\TenantContext;
use Throwable;


final class AppointmentReminderService
{
    private AppointmentModel $appointmentModel;
    private TenantModel $tenantModel;
    private ReminderEmailTemplate $template;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->tenantModel      = new TenantModel();
        $this->template         = new ReminderEmailTemplate();
        $this->emailSender      = new EmailSenderService();
    }

    
    public function sendToAllActiveTenants(): array
    {
        $results = [];

        foreach ($this->tenantModel->findAllActive() as $tenant) {
            $previous = TenantContext::current();
            TenantContext::set($tenant);

            try {
                $results[] = ['tenant' => $tenant] + $this->sendForCurrentTenant();
            } catch (Throwable $e) {
                log_message('error', "Recordatorios: falló el Tenant {$tenant->getSlug()}: {$e->getMessage()}");
                $results[] = [
                    'tenant'   => $tenant,
                    'found'    => 0,
                    'sent'     => 0,
                    'errors'   => 1,
                    'failures' => ["No se pudo procesar el Tenant: {$e->getMessage()}"],
                ];
            } finally {
                if ($previous !== null) {
                    TenantContext::set($previous);
                } else {
                    TenantContext::clear();
                }
            }
        }

        return $results;
    }

    public function sendForCurrentTenant(): array
    {
        $appointments = $this->appointmentModel->findUpcomingIn24Hours();

        $sent     = 0;
        $failures = [];

        // Una instancia por Tenant: lee la configuración del Tenant actual del TenantContext.
        $settingsResolver = new TenantEmailSettingsResolverService();
        $replyTo = $settingsResolver->replyTo();

        foreach ($appointments as $projection) {
            try {
                $request = new EmailRequest(
                    to: $projection->getEmail(),
                    subject: 'Recordatorio: tenés un turno mañana',
                    body: $this->template->render($projection),
                    replyTo: $replyTo,
                );

                $this->emailSender->send($request);
                $sent++;
            } catch (Throwable $e) {
                log_message('error', "Recordatorio del turno {$projection->getId()} no enviado: {$e->getMessage()}");
                $failures[] = "Turno #{$projection->getId()} ({$projection->getEmail()}): {$e->getMessage()}";
            }
        }

        return [
            'found'    => count($appointments),
            'sent'     => $sent,
            'errors'   => count($failures),
            'failures' => $failures,
        ];
    }
}

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
    /** @var callable(EmailRequest): mixed */
    private $sender;

 
    public function __construct(?callable $sender = null)
    {
        $this->appointmentModel = new AppointmentModel();
        $this->tenantModel      = new TenantModel();
        $this->template         = new ReminderEmailTemplate();
        $this->sender           = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
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
                $previous !== null ? TenantContext::set($previous) : TenantContext::clear();
            }
        }

        return $results;
    }

    public function sendForCurrentTenant(): array
    {
        $appointments = $this->appointmentModel->findUpcomingIn24Hours();

        $sent     = 0;
        $failures = [];

      
        $replyTo = (new TenantEmailSettingsResolverService())->replyTo();

        foreach ($appointments as $projection) {
            try {
                ($this->sender)(new EmailRequest(
                    to:      $projection->getEmail(),
                    subject: 'Recordatorio: tenés un turno mañana',
                    body:    $this->template->render($projection),
                    replyTo: $replyTo,
                ));
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

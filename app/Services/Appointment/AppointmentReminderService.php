<?php

namespace App\Services\Appointment;

use App\Dto\Request\Email\EmailRequest;
use App\Email\Appointment\AppointmentEmailTemplateRenderer;
use App\Email\EmailSenderService;
use App\Entity\Tenant\Tenant;
use App\Models\Appointment\AppointmentModel;
use App\Models\Tenant\TenantModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use App\Tenancy\TenantContext;
use Throwable;

/**
 * Recordatorios por email de los turnos de las próximas 24 horas.
 *
 * Corre sin request (CLI), así que no hay Tenant: recorre los Tenants activos (los suspendidos se
 * saltean), fija el TenantContext de cada uno, envía solo los turnos de ese Tenant y lo limpia al
 * terminar (restaura el que hubiera antes). Un fallo en un envío o en un Tenant se registra y no
 * corta a los demás.
 */
final class AppointmentReminderService
{
    private AppointmentModel $appointmentModel;
    private TenantModel $tenantModel;
    private AppointmentEmailTemplateRenderer $renderer;
    /** @var callable(EmailRequest): mixed */
    private $sender;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->appointmentModel = new AppointmentModel();
        $this->tenantModel      = new TenantModel();
        $this->renderer         = new AppointmentEmailTemplateRenderer();
        $this->sender           = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
    }

    /**
     * @return list<array{tenant: Tenant, found: int, sent: int, errors: int, failures: list<string>}>
     */
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

    /**
     * Envía los recordatorios del Tenant que esté en el TenantContext.
     *
     * @return array{found: int, sent: int, errors: int, failures: list<string>}
     */
    public function sendForCurrentTenant(): array
    {
        $appointments = $this->appointmentModel->findUpcomingIn24Hours();

        $sent     = 0;
        $failures = [];

        // Una instancia por Tenant: el resolver guarda la fila que lee.
        $replyTo = (new TenantEmailSettingsResolverService())->replyTo();

        foreach ($appointments as $projection) {
            try {
                ($this->sender)(new EmailRequest(
                    to:      $projection->getEmail(),
                    subject: 'Recordatorio: tenés un turno mañana',
                    body:    $this->renderer->renderReminderBody($projection),
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

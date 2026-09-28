<?php

namespace App\Commands;

use App\Dto\Request\Email\EmailRequest;
use App\Email\Appointment\AppointmentEmailTemplateRenderer;
use App\Email\EmailSenderService;
use App\Models\Appointment\AppointmentModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendAppointmentRemindersCommand extends BaseCommand
{
    protected $group       = 'Appointments';
    protected $name        = 'appointments:remind';
    protected $description = 'Envía recordatorios por email a los pacientes con turno en las próximas 24 horas.';

    public function run(array $params): void
    {
        $model    = new AppointmentModel();
        $renderer = new AppointmentEmailTemplateRenderer();
        $sender   = new EmailSenderService();

        $appointments = $model->findUpcomingIn24Hours();

        if (empty($appointments)) {
            CLI::write('No hay turnos próximos en 24 horas.', 'yellow');
            return;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($appointments as $projection) {
            try {
                $body = $renderer->renderReminderBody($projection);
                $sender->send(new EmailRequest(
                    to:      $projection->getEmail(),
                    subject: 'Recordatorio: tenés un turno mañana',
                    body:    $body,
                ));
                CLI::write("✓ Recordatorio enviado a {$projection->getEmail()} (turno #{$projection->getId()})", 'green');
                $sent++;
            } catch (\Throwable $e) {
                CLI::write("✗ Error al enviar a {$projection->getEmail()}: {$e->getMessage()}", 'red');
                $errors++;
            }
        }

        CLI::write("---", 'white');
        CLI::write("Enviados: {$sent} | Errores: {$errors}", 'white');
    }
}

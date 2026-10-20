<?php

namespace App\Commands;

use App\Services\Appointment\AppointmentReminderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use Psr\Log\LoggerInterface;

class SendAppointmentRemindersCommand extends BaseCommand
{
    protected $group       = 'Appointments';
    protected $name        = 'appointments:remind';
    protected $description = 'Envía recordatorios por email a los pacientes con turno en las próximas 24 horas, Tenant por Tenant (se saltean los suspendidos).';

    private AppointmentReminderService $reminderService;

    public function __construct(LoggerInterface $logger, Commands $commands)
    {
        parent::__construct($logger, $commands);

        $this->reminderService = new AppointmentReminderService();
    }

    public function run(array $params): void
    {
        $results = $this->reminderService->sendToAllActiveTenants();

        if ($results === []) {
            CLI::write('No hay Tenants activos.', 'yellow');
            return;
        }

        $totalSent   = 0;
        $totalErrors = 0;

        foreach ($results as $result) {
            $this->printTenantResult($result);

            $totalSent   += $result['sent'];
            $totalErrors += $result['errors'];
        }

        CLI::write('---', 'white');
        CLI::write("Total enviados: {$totalSent} | Total errores: {$totalErrors}", 'white');
    }

    private function printTenantResult(array $result): void
    {
        $slug = $result['tenant']->getSlug();

        if ($result['found'] === 0 && $result['errors'] === 0) {
            CLI::write("[{$slug}] No hay turnos próximos en 24 horas.", 'yellow');
            return;
        }

        CLI::write("[{$slug}] Enviados: {$result['sent']} | Errores: {$result['errors']}", 'green');

        foreach ($result['failures'] as $failure) {
            CLI::write("  ✗ {$failure}", 'red');
        }
    }
}

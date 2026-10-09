<?php

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * El comando es un cascarón: recorre los Tenants activos con AppointmentReminderService (cuya
 * lógica se prueba en AppointmentReminderServiceTest). Acá solo se verifica lo que imprime.
 * Sin turnos para mañana no se envía ningún email.
 *
 * @internal
 */
final class SendAppointmentRemindersCommandTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use StreamFilterTrait;

    protected $refresh = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStreamFilterTrait();
    }

    protected function tearDown(): void
    {
        $this->tearDownStreamFilterTrait();
        parent::tearDown();
    }

    public function testReportsEachActiveTenantByItsSlug(): void
    {
        command('appointments:remind');

        $output = $this->getStreamFilterBuffer();
        $this->assertStringContainsString('[demo] No hay turnos próximos en 24 horas.', $output);
    }

    public function testReportsWhenThereAreNoActiveTenants(): void
    {
        $this->db->query("UPDATE tenants SET status = 'suspended'");

        command('appointments:remind');

        $this->assertStringContainsString('No hay Tenants activos.', $this->getStreamFilterBuffer());
    }
}

<?php

use App\Services\Metrics\MetricsRecentAppointmentsService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Prueba el mapeo de una fila de DB a camelCase con casts de tipo,
 * sin tocar la base de datos (método privado vía Reflection).
 *
 * @internal
 */
final class MetricsRecentAppointmentsServiceTest extends CIUnitTestCase
{
    private function buildItem(object $row): array
    {
        $reflection = new ReflectionMethod(MetricsRecentAppointmentsService::class, 'buildItem');
        $reflection->setAccessible(true);

        return $reflection->invoke(new MetricsRecentAppointmentsService(), $row);
    }

    public function testMapsRowToCamelCaseAndCastsId(): void
    {
        $row = (object) [
            'appointment_id' => '7',
            'patient_name' => 'Ana Paz',
            'medical_name' => 'Raul Marquez',
            'service_name' => 'Revision',
            'fecha' => '2026-09-30',
            'hora' => '10:00:00',
            'status' => 'pending',
            'created_at' => '2026-09-29 10:00:00',
            'updated_at' => '2026-09-30 11:00:00',
        ];

        $item = $this->buildItem($row);

        $this->assertSame(7, $item['appointmentId']);
        $this->assertSame('Ana Paz', $item['patientName']);
        $this->assertSame('Raul Marquez', $item['medicalName']);
        $this->assertSame('Revision', $item['serviceName']);
        $this->assertSame('pending', $item['status']);
        $this->assertSame('2026-09-30 11:00:00', $item['updatedAt']);
    }

    public function testServiceNameCanBeNull(): void
    {
        $row = (object) [
            'appointment_id' => 1, 'patient_name' => 'A', 'medical_name' => 'B', 'service_name' => null,
            'fecha' => '2026-09-30', 'hora' => '10:00:00', 'status' => 'pending',
            'created_at' => null, 'updated_at' => null,
        ];

        $this->assertNull($this->buildItem($row)['serviceName']);
    }
}

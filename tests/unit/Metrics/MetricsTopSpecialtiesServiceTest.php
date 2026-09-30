<?php

use App\Services\Metrics\MetricsTopSpecialtiesService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Prueba el mapeo de una fila de DB a camelCase con casts de tipo,
 * sin tocar la base de datos (método privado vía Reflection).
 *
 * @internal
 */
final class MetricsTopSpecialtiesServiceTest extends CIUnitTestCase
{
    private function buildItem(object $row): array
    {
        $reflection = new ReflectionMethod(MetricsTopSpecialtiesService::class, 'buildItem');
        $reflection->setAccessible(true);

        return $reflection->invoke(new MetricsTopSpecialtiesService(), $row);
    }

    public function testMapsRowToCamelCaseAndCastsNumbers(): void
    {
        $item = $this->buildItem((object) [
            'specialty_id' => '3',
            'specialty_name' => 'Pediatría',
            'total' => '12',
        ]);

        $this->assertSame(['specialtyId' => 3, 'specialtyName' => 'Pediatría', 'total' => 12], $item);
    }
}

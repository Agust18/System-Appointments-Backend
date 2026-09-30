<?php

use App\Services\Metrics\MetricsKpisService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Prueba el armado de KPIs y el cálculo de tasas (ausentismo y cancelación),
 * sin tocar la base de datos (se invoca el método privado vía Reflection).
 *
 * @internal
 */
final class MetricsKpisServiceTest extends CIUnitTestCase
{
    private function buildKpis(int $total, int $cancelled, int $completed, int $noshow): array
    {
        // La DB devuelve strings (COUNT/SUM); se simula igual.
        $row = (object) [
            'total' => (string) $total,
            'pending' => '0',
            'confirmed' => '0',
            'cancelled' => (string) $cancelled,
            'completed' => (string) $completed,
            'noshow' => (string) $noshow,
            'revenue' => null,
        ];

        $reflection = new ReflectionMethod(MetricsKpisService::class, 'buildKpis');
        $reflection->setAccessible(true);

        return $reflection->invoke(new MetricsKpisService(), $row);
    }

    public function testRatesAreZeroWhenDenominatorsAreZero(): void
    {
        $kpis = $this->buildKpis(0, 0, 0, 0);

        $this->assertSame(0.0, $kpis['noshowRate']);
        $this->assertSame(0.0, $kpis['cancellationRate']);
    }

    public function testNoshowRateUsesCompletedPlusNoshowAsDenominator(): void
    {
        // 2 / (6 + 2) = 25 %; el total (20) no interviene.
        $this->assertSame(25.0, $this->buildKpis(20, 0, 6, 2)['noshowRate']);
    }

    public function testCancellationRateUsesTotalAsDenominator(): void
    {
        // 5 / 20 = 25 %
        $this->assertSame(25.0, $this->buildKpis(20, 5, 0, 0)['cancellationRate']);
    }

    public function testRatesAreRoundedToOneDecimal(): void
    {
        // 1 / 3 = 33.333... → 33.3 ; 2 / 3 = 66.666... → 66.7
        $kpis = $this->buildKpis(3, 2, 2, 1);

        $this->assertSame(33.3, $kpis['noshowRate']);
        $this->assertSame(66.7, $kpis['cancellationRate']);
    }

    public function testNoshowRateIsZeroWhenOnlyCompletedExist(): void
    {
        $this->assertSame(0.0, $this->buildKpis(4, 0, 4, 0)['noshowRate']);
    }

    public function testCountsAreCastToIntAndRevenueToFloat(): void
    {
        $kpis = $this->buildKpis(4, 1, 2, 1);

        $this->assertSame(4, $kpis['total']);
        $this->assertSame(0.0, $kpis['revenue']);
    }
}

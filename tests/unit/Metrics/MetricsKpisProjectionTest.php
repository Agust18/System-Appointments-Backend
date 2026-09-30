<?php

use App\Entity\Metrics\Projection\MetricsKpisProjection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Prueba el cálculo de tasas (ausentismo y cancelación) de la Projection de KPIs.
 *
 * @internal
 */
final class MetricsKpisProjectionTest extends CIUnitTestCase
{
    private function buildProjection(int $total, int $cancelled, int $completed, int $noshow): MetricsKpisProjection
    {
        return new MetricsKpisProjection(
            total: $total,
            pending: 0,
            confirmed: 0,
            cancelled: $cancelled,
            completed: $completed,
            noshow: $noshow,
            revenue: 0.0
        );
    }

    public function testRatesAreZeroWhenDenominatorsAreZero(): void
    {
        $projection = $this->buildProjection(0, 0, 0, 0);

        $this->assertSame(0.0, $projection->getNoshowRate());
        $this->assertSame(0.0, $projection->getCancellationRate());
    }

    public function testNoshowRateUsesCompletedPlusNoshowAsDenominator(): void
    {
        // 2 / (6 + 2) = 25 %; el total (20) no interviene.
        $projection = $this->buildProjection(20, 0, 6, 2);

        $this->assertSame(25.0, $projection->getNoshowRate());
    }

    public function testCancellationRateUsesTotalAsDenominator(): void
    {
        // 5 / 20 = 25 %
        $projection = $this->buildProjection(20, 5, 0, 0);

        $this->assertSame(25.0, $projection->getCancellationRate());
    }

    public function testRatesAreRoundedToOneDecimal(): void
    {
        // 1 / 3 = 33.333... → 33.3 ; 2 / 3 = 66.666... → 66.7
        $projection = $this->buildProjection(3, 2, 2, 1);

        $this->assertSame(33.3, $projection->getNoshowRate());
        $this->assertSame(66.7, $projection->getCancellationRate());
    }

    public function testNoshowRateIsZeroWhenOnlyCompletedExist(): void
    {
        $projection = $this->buildProjection(4, 0, 4, 0);

        $this->assertSame(0.0, $projection->getNoshowRate());
    }
}

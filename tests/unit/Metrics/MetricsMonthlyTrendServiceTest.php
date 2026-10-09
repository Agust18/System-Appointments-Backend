<?php

use App\Services\Metrics\MetricsMonthlyTrendService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Prueba la lógica de ventana fija de 6 meses y el relleno de meses en 0,
 * sin tocar la base de datos (se invocan los métodos privados vía Reflection).
 *
 * @internal
 */
final class MetricsMonthlyTrendServiceTest extends CIUnitTestCase
{
    private MetricsMonthlyTrendService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetricsMonthlyTrendService();
    }

    private function invokePrivate(string $method, array $args)
    {
        $reflection = new ReflectionMethod(MetricsMonthlyTrendService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->service, $args);
    }

    public function testBuildMonthsWindowReturnsSixMonthsInChronologicalOrder(): void
    {
        $months = $this->invokePrivate('buildMonthsWindow', []);

        $this->assertCount(6, $months);
        $this->assertSame(date('Y-m'), end($months));
        $this->assertSame(date('Y-m', strtotime('-5 months')), reset($months));
        $this->assertSame($months, array_unique($months));
    }

    public function testFillGapsFillsMissingMonthsWithZeros(): void
    {
        $months = ['2026-01', '2026-02', '2026-03'];

        $row = new stdClass();
        $row->month = '2026-02';
        $row->total = '5';
        $row->completed = '3';
        $row->cancelled = '1';
        $row->noshow = '1';

        $responses = $this->invokePrivate('fillGaps', [$months, [$row]]);

        $this->assertCount(3, $responses);

        $this->assertSame('2026-01', $responses[0]["month"]);
        $this->assertSame(0, $responses[0]["total"]);

        $this->assertSame('2026-02', $responses[1]["month"]);
        $this->assertSame(5, $responses[1]["total"]);
        $this->assertSame(3, $responses[1]["completed"]);
        $this->assertSame(1, $responses[1]["cancelled"]);
        $this->assertSame(1, $responses[1]["noshow"]);

        $this->assertSame('2026-03', $responses[2]["month"]);
        $this->assertSame(0, $responses[2]["total"]);
    }
}

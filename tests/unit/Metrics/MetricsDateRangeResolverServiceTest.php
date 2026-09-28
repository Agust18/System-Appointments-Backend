<?php

use App\Exception\Validation\ValidationException;
use App\Services\Metrics\MetricsDateRangeResolverService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MetricsDateRangeResolverServiceTest extends CIUnitTestCase
{
    private MetricsDateRangeResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetricsDateRangeResolverService();
    }

    public function testDefaultsToFirstDayOfCurrentMonthAndToday(): void
    {
        [$dateFrom, $dateTo] = $this->service->resolve(null, null);

        $this->assertSame(date('Y-m-01'), $dateFrom);
        $this->assertSame(date('Y-m-d'), $dateTo);
    }

    public function testKeepsExplicitDatesWhenValid(): void
    {
        [$dateFrom, $dateTo] = $this->service->resolve('2026-01-01', '2026-01-31');

        $this->assertSame('2026-01-01', $dateFrom);
        $this->assertSame('2026-01-31', $dateTo);
    }

    public function testRejectsInvalidDateFormat(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->resolve('01-01-2026', '2026-01-31');
    }

    public function testRejectsNonExistentDate(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->resolve('2026-02-30', '2026-03-01');
    }
}

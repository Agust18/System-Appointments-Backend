<?php

use App\Entity\MedicalService\MedicalService;
use App\Entity\Service\Service;
use App\Services\Appointment\ServiceDurationCalculatorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ServiceDurationCalculatorServiceTest extends CIUnitTestCase
{
    private ServiceDurationCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ServiceDurationCalculatorService();
    }

    private function makeService(int $durationMinutes, int $bufferMinutes): Service
    {
        return new Service(1, 'Servicio', null, $durationMinutes, $bufferMinutes, 100.0, true, null, null);
    }

    private function makeMedicalService(?int $customDurationMinutes): MedicalService
    {
        return new MedicalService(1, 1, 1, null, $customDurationMinutes, true, null, null);
    }

    public function testUsesBaseDurationWhenNoOverride(): void
    {
        $duration = $this->calculator->calculate($this->makeService(30, 10), $this->makeMedicalService(null));

        $this->assertSame(30, $duration->getEffectiveDurationMinutes());
        $this->assertSame(10, $duration->getBufferMinutes());
        $this->assertSame(40, $duration->getTotalBlockMinutes());
    }

    public function testUsesMedicalOverrideWhenPresent(): void
    {
        $duration = $this->calculator->calculate($this->makeService(30, 10), $this->makeMedicalService(20));

        $this->assertSame(20, $duration->getEffectiveDurationMinutes());
        $this->assertSame(10, $duration->getBufferMinutes());
        $this->assertSame(30, $duration->getTotalBlockMinutes());
    }

    public function testZeroBufferAddsNothingToTotalBlock(): void
    {
        $duration = $this->calculator->calculate($this->makeService(45, 0), $this->makeMedicalService(null));

        $this->assertSame(45, $duration->getTotalBlockMinutes());
    }
}

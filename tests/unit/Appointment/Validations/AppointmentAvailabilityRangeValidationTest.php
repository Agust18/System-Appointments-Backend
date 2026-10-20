<?php

use App\Exception\Validation\ValidationException;
use App\Services\Appointment\Validations\AppointmentAvailabilityValidatorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppointmentAvailabilityRangeValidationTest extends CIUnitTestCase
{
    private AppointmentAvailabilityValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AppointmentAvailabilityValidatorService();
    }

    public function testAcceptsValidRangeWithin60Days(): void
    {
        $this->validator->validateRange('2026-09-15', '2026-09-28');
        $this->addToAssertionCount(1);
    }

    public function testRejectsInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateRange('15-09-2026', '2026-09-28');
    }

    public function testRejectsFromAfterTo(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateRange('2026-09-28', '2026-09-15');
    }

    public function testRejectsRangeOver60Days(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateRange('2026-01-01', '2026-12-31');
    }

    public function testAcceptsExactly60DaysRange(): void
    {
        $this->validator->validateRange('2026-01-01', '2026-03-02'); // 60 días exactos
        $this->addToAssertionCount(1);
    }
}

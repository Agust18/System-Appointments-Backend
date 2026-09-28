<?php

use App\Exception\Validation\ValidationException;
use App\Services\Appointment\Validations\AppointmentAvailabilityQueryValidatorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppointmentAvailabilityQueryValidatorServiceTest extends CIUnitTestCase
{
    private AppointmentAvailabilityQueryValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AppointmentAvailabilityQueryValidatorService();
    }

    public function testAcceptsPositiveIds(): void
    {
        $this->validator->validate(1, 1);
        $this->addToAssertionCount(1);
    }

    public function testRejectsZeroMedicalId(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(0, 1);
    }

    public function testRejectsNegativeServiceId(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(1, -1);
    }
}

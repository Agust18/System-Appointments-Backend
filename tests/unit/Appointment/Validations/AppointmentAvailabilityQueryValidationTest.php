<?php

use App\Exception\Validation\ValidationException;
use App\Services\Appointment\Validations\AppointmentAvailabilityValidatorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppointmentAvailabilityQueryValidationTest extends CIUnitTestCase
{
    private AppointmentAvailabilityValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AppointmentAvailabilityValidatorService();
    }

    public function testAcceptsPositiveIds(): void
    {
        $this->validator->validateQuery(1, 1);
        $this->addToAssertionCount(1);
    }

    public function testRejectsZeroMedicalId(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateQuery(0, 1);
    }

    public function testRejectsNegativeServiceId(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateQuery(1, -1);
    }
}

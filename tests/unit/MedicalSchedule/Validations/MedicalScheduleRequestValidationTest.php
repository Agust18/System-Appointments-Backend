<?php

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MedicalScheduleRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'medicalId' => 1,
            'dayOfWeek' => 3,
            'startTime' => '09:00',
            'endTime'   => '17:00',
        ];
    }

    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate($this->validPayload(), MedicalScheduleRequest::rules(), MedicalScheduleRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingMedicalIdThrows422(): void
    {
        $payload = $this->validPayload();
        unset($payload['medicalId']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, MedicalScheduleRequest::rules(), MedicalScheduleRequest::messages());
    }

    public function testInvalidStartTimeFormatThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['startTime'] = 'nueve de la mañana';

        try {
            RequestValidator::validate($payload, MedicalScheduleRequest::rules(), MedicalScheduleRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('startTime', $e->getErrors());
        }
    }

    public function testDayOfWeekOutOfRangeThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['dayOfWeek'] = 8;

        try {
            RequestValidator::validate($payload, MedicalScheduleRequest::rules(), MedicalScheduleRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('dayOfWeek', $e->getErrors());
        }
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], MedicalScheduleUpdateRequest::rules(), MedicalScheduleUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }
}

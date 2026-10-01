<?php

use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MedicalDataRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'tuition'     => 'MP-1234',
            'specialtyId' => 1,
        ];
    }

    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate($this->validPayload(), MedicalDataRequest::rules(), MedicalDataRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingTuitionThrows422(): void
    {
        $payload = $this->validPayload();
        unset($payload['tuition']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, MedicalDataRequest::rules(), MedicalDataRequest::messages());
    }

    public function testInvalidSpecialtyIdThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['specialtyId'] = 0;

        try {
            RequestValidator::validate($payload, MedicalDataRequest::rules(), MedicalDataRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('specialtyId', $e->getErrors());
        }
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], MedicalUpdateRequest::rules(), MedicalUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testUpdateInvalidSpecialtyIdThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['specialtyId' => -1], MedicalUpdateRequest::rules(), MedicalUpdateRequest::messages());
    }
}

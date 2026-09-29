<?php

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientPublicRegisterRequest;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PatientDataRequestValidationTest extends CIUnitTestCase
{
    public function testValidBirthDatePasses(): void
    {
        RequestValidator::validate(['birthDate' => '1990-05-20'], PatientDataRequest::rules(), PatientDataRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingBirthDateThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate([], PatientDataRequest::rules(), PatientDataRequest::messages());
    }

    public function testInvalidBirthDateFormatThrows422(): void
    {
        try {
            RequestValidator::validate(['birthDate' => '20-05-1990'], PatientDataRequest::rules(), PatientDataRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('birthDate', $e->getErrors());
        }
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], PatientUpdateRequest::rules(), PatientUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testUpdateInvalidBirthDateThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['birthDate' => 'no-es-fecha'], PatientUpdateRequest::rules(), PatientUpdateRequest::messages());
    }

    private function validPublicRegisterPayload(): array
    {
        return [
            'firstName' => 'Juana',
            'lastName'  => 'Pérez',
            'email'     => 'juana@example.com',
            'password'  => 'contrasenia123',
            'dni'       => '30123456',
        ];
    }

    public function testPublicRegisterValidPayloadPasses(): void
    {
        RequestValidator::validate($this->validPublicRegisterPayload(), PatientPublicRegisterRequest::rules(), PatientPublicRegisterRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testPublicRegisterMissingFirstNameThrows422(): void
    {
        $payload = $this->validPublicRegisterPayload();
        unset($payload['firstName']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, PatientPublicRegisterRequest::rules(), PatientPublicRegisterRequest::messages());
    }

    public function testPublicRegisterInvalidDniThrows422(): void
    {
        $payload = $this->validPublicRegisterPayload();
        $payload['dni'] = 'abc123';

        try {
            RequestValidator::validate($payload, PatientPublicRegisterRequest::rules(), PatientPublicRegisterRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('dni', $e->getErrors());
        }
    }
}

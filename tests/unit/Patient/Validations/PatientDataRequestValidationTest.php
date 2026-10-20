<?php

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientSelfRegisterRequest;
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

    public function testMissingBirthDatePasses(): void
    {
        // La fecha de nacimiento es opcional: la reserva pública no la pide.
        RequestValidator::validate([], PatientDataRequest::rules(), PatientDataRequest::messages());
        $this->addToAssertionCount(1);
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

    public function testUpdateAcceptsContactFields(): void
    {
        RequestValidator::validate(
            ['firstName' => 'Juana', 'lastName' => 'Pérez', 'phone' => '2325506499', 'email' => 'juana@example.com'],
            PatientUpdateRequest::rules(),
            PatientUpdateRequest::messages()
        );
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider invalidContactFieldsProvider
     */
    public function testUpdateRejectsInvalidContactFields(string $field, string $value): void
    {
        try {
            RequestValidator::validate([$field => $value], PatientUpdateRequest::rules(), PatientUpdateRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->getErrors());
        }
    }

    public static function invalidContactFieldsProvider(): array
    {
        return [
            'email inválido'    => ['email', 'no-es-un-email'],
            'teléfono con letras' => ['phone', '2325abc499'],
            'teléfono corto'    => ['phone', '2325'],
            'nombre corto'      => ['firstName', 'J'],
        ];
    }

    public function testUpdateWithoutContactFieldsDoesNotTouchTheUser(): void
    {
        $this->assertNull((new PatientUpdateRequest('1990-05-20'))->toUserUpdaterRequest());
    }

    public function testUpdateWithContactFieldsBuildsUserUpdaterRequest(): void
    {
        $userRequest = (new PatientUpdateRequest(null, phone: '2325506499', email: 'juana@example.com'))->toUserUpdaterRequest();

        $this->assertNotNull($userRequest);
        $this->assertSame('2325506499', $userRequest->getPhone());
        $this->assertSame('juana@example.com', $userRequest->getEmail());
        $this->assertNull($userRequest->getFirstName());
        $this->assertNull($userRequest->getPassword());
        $this->assertNull($userRequest->getDni());
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
        RequestValidator::validate($this->validPublicRegisterPayload(), PatientSelfRegisterRequest::rules(), PatientSelfRegisterRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testPublicRegisterSevenDigitDniPasses(): void
    {
        $payload = $this->validPublicRegisterPayload();
        $payload['dni'] = '7123456';

        RequestValidator::validate($payload, PatientSelfRegisterRequest::rules(), PatientSelfRegisterRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testPublicRegisterMissingFirstNameThrows422(): void
    {
        $payload = $this->validPublicRegisterPayload();
        unset($payload['firstName']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, PatientSelfRegisterRequest::rules(), PatientSelfRegisterRequest::messages());
    }

    public function testPublicRegisterInvalidDniThrows422(): void
    {
        $payload = $this->validPublicRegisterPayload();
        $payload['dni'] = 'abc123';

        try {
            RequestValidator::validate($payload, PatientSelfRegisterRequest::rules(), PatientSelfRegisterRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('dni', $e->getErrors());
        }
    }
}

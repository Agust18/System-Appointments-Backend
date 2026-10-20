<?php

use App\Dto\Request\User\UserRequest;
use App\Dto\Request\User\UserUpdaterRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UserRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'firstName' => 'Juana',
            'lastName'  => 'Pérez',
            'email'     => 'juana@example.com',
            'password'  => 'contrasenia123',
            'dni'       => 30123456,
            'roleId'    => 4,
        ];
    }

    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate($this->validPayload(), UserRequest::rules(), UserRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingFirstNameThrows422(): void
    {
        $payload = $this->validPayload();
        unset($payload['firstName']);

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertArrayHasKey('firstName', $e->getErrors());
        }
    }

    public function testInvalidEmailFormatThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['email'] = 'no-es-un-email';

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
    }

    public function testEmailLongerThanColumnThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['email'] = str_repeat('a', 142) . '@mail.com'; // 151 caracteres: la columna es varchar(150)

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->getErrors());
        }
    }

    public function testRoleIdOutOfRangeThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['roleId'] = 99;

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('roleId', $e->getErrors());
        }
    }

    public function testMissingRoleIdThrows422(): void
    {
        $payload = $this->validPayload();
        unset($payload['roleId']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
    }

    public function testSevenDigitDniPasses(): void
    {
        $payload = $this->validPayload();
        $payload['dni'] = '7123456';

        RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider invalidDniProvider
     */
    public function testInvalidDniThrows422(string $dni): void
    {
        $payload = $this->validPayload();
        $payload['dni'] = $dni;

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('dni', $e->getErrors());
        }
    }

    public static function invalidDniProvider(): array
    {
        return [
            'muy corto'       => ['123456'],
            'muy largo'       => ['123456789'],
            'con letras'      => ['12ab5678'],
            'sin normalizar'  => ['23.525.064'],
        ];
    }

    public function testNormalizedPhonePasses(): void
    {
        $payload = $this->validPayload();
        $payload['phone'] = '2325506499';

        RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testNullPhonePasses(): void
    {
        $payload = $this->validPayload();
        $payload['phone'] = null;

        RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider invalidPhoneProvider
     */
    public function testInvalidPhoneThrows422(string $phone): void
    {
        $payload = $this->validPayload();
        $payload['phone'] = $phone;

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('phone', $e->getErrors());
        }
    }

    public static function invalidPhoneProvider(): array
    {
        return [
            'muy corto'  => ['232550'],
            'muy largo'  => ['1234567890123456'],
            'con letras' => ['2325abc499'],
        ];
    }

    public function testPasswordIsRequiredByDefault(): void
    {
        $payload = $this->validPayload();
        unset($payload['password']);

        try {
            RequestValidator::validate($payload, UserRequest::rules(), UserRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password', $e->getErrors());
        }
    }

    public function testPasswordCanBeOmittedWhenNotRequired(): void
    {
        $payload = $this->validPayload();
        unset($payload['password']);

        RequestValidator::validate($payload, UserRequest::rules(passwordRequired: false), UserRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testShortPasswordStillFailsWhenNotRequired(): void
    {
        $payload = $this->validPayload();
        $payload['password'] = '123';

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, UserRequest::rules(passwordRequired: false), UserRequest::messages());
    }

    public function testUpdaterAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], UserUpdaterRequest::rules(), UserUpdaterRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testUpdaterInvalidEmailThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['email' => 'no-es-un-email'], UserUpdaterRequest::rules(), UserUpdaterRequest::messages());
    }

    public function testUpdaterShortPasswordThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['password' => '123'], UserUpdaterRequest::rules(), UserUpdaterRequest::messages());
    }
}

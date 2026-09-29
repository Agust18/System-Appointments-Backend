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
            'roleId'    => 3,
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

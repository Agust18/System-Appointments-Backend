<?php

use App\Dto\Request\Auth\ForgotPasswordRequest;
use App\Dto\Request\Auth\ResendVerificationEmailRequest;
use App\Dto\Request\Auth\ResetPasswordRequest;
use App\Dto\Request\Auth\UserLoginRequest;
use App\Dto\Request\Auth\VerifyEmailRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthRequestValidationTest extends CIUnitTestCase
{
    public function testLoginValidPayloadPasses(): void
    {
        RequestValidator::validate(
            ['email' => 'juana@example.com', 'password' => 'contrasenia123'],
            UserLoginRequest::rules(),
            UserLoginRequest::messages()
        );
        $this->addToAssertionCount(1);
    }

    public function testLoginMissingEmailThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['password' => 'contrasenia123'], UserLoginRequest::rules(), UserLoginRequest::messages());
    }

    public function testLoginMissingPasswordThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['email' => 'juana@example.com'], UserLoginRequest::rules(), UserLoginRequest::messages());
    }

    public function testLoginInvalidEmailFormatThrows422(): void
    {
        try {
            RequestValidator::validate(
                ['email' => 'no-es-un-email', 'password' => 'contrasenia123'],
                UserLoginRequest::rules(),
                UserLoginRequest::messages()
            );
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->getErrors());
        }
    }

    public function testForgotPasswordValidEmailPasses(): void
    {
        RequestValidator::validate(['email' => 'juana@example.com'], ForgotPasswordRequest::rules(), ForgotPasswordRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testForgotPasswordMissingEmailThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate([], ForgotPasswordRequest::rules(), ForgotPasswordRequest::messages());
    }

    public function testResetPasswordValidPayloadPasses(): void
    {
        RequestValidator::validate(
            ['token' => 'abc123', 'password' => 'contrasenia123'],
            ResetPasswordRequest::rules(),
            ResetPasswordRequest::messages()
        );
        $this->addToAssertionCount(1);
    }

    public function testResetPasswordShortPasswordThrows422(): void
    {
        try {
            RequestValidator::validate(
                ['token' => 'abc123', 'password' => '123'],
                ResetPasswordRequest::rules(),
                ResetPasswordRequest::messages()
            );
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password', $e->getErrors());
        }
    }

    public function testVerifyEmailMissingTokenThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['token' => null], VerifyEmailRequest::rules(), VerifyEmailRequest::messages());
    }

    public function testResendVerificationInvalidEmailThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['email' => 'no-es-un-email'], ResendVerificationEmailRequest::rules(), ResendVerificationEmailRequest::messages());
    }
}

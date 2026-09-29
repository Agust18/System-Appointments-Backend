<?php

use App\Dto\Request\Role\RoleRequest;
use App\Dto\Request\Role\RoleUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RoleRequestValidationTest extends CIUnitTestCase
{
    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate(['name' => 'Enfermería'], RoleRequest::rules(), RoleRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingNameThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate([], RoleRequest::rules(), RoleRequest::messages());
    }

    public function testTooShortNameThrows422(): void
    {
        try {
            RequestValidator::validate(['name' => 'ab'], RoleRequest::rules(), RoleRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertArrayHasKey('name', $e->getErrors());
        }
    }

    public function testTooLongNameThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate(['name' => str_repeat('a', 21)], RoleRequest::rules(), RoleRequest::messages());
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], RoleUpdateRequest::rules(), RoleUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }
}

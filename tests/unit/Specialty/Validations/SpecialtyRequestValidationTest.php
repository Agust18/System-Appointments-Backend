<?php

use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Dto\Request\Specialty\SpecialtyUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SpecialtyRequestValidationTest extends CIUnitTestCase
{
    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate(['name' => 'Cardiología'], SpecialtyRequest::rules(), SpecialtyRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingNameThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate([], SpecialtyRequest::rules(), SpecialtyRequest::messages());
    }

    public function testTooShortNameThrows422(): void
    {
        try {
            RequestValidator::validate(['name' => 'ab'], SpecialtyRequest::rules(), SpecialtyRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertArrayHasKey('name', $e->getErrors());
        }
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], SpecialtyUpdateRequest::rules(), SpecialtyUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }
}

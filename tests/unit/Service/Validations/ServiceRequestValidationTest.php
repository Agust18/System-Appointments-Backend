<?php

use App\Dto\Request\Service\ServiceRequest;
use App\Dto\Request\Service\ServiceUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ServiceRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'name'            => 'Consulta general',
            'description'     => 'Consulta médica de rutina',
            'durationMinutes' => 30,
        ];
    }

    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate($this->validPayload(), ServiceRequest::rules(), ServiceRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingNameThrows422(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, ServiceRequest::rules(), ServiceRequest::messages());
    }

    public function testDurationOverLimitThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['durationMinutes'] = 999;

        try {
            RequestValidator::validate($payload, ServiceRequest::rules(), ServiceRequest::messages());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('durationMinutes', $e->getErrors());
        }
    }

    public function testNegativePriceThrows422(): void
    {
        $payload = $this->validPayload();
        $payload['price'] = -5;

        $this->expectException(ValidationException::class);
        RequestValidator::validate($payload, ServiceRequest::rules(), ServiceRequest::messages());
    }

    public function testUpdateAllowsEmptyPayload(): void
    {
        RequestValidator::validate([], ServiceUpdateRequest::rules(), ServiceUpdateRequest::messages());
        $this->addToAssertionCount(1);
    }
}

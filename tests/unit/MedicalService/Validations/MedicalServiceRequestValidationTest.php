<?php

use App\Dto\Request\MedicalService\MedicalServiceRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MedicalServiceRequestValidationTest extends CIUnitTestCase
{
    public function testValidPayloadPasses(): void
    {
        RequestValidator::validate(['serviceId' => 1], MedicalServiceRequest::rules(), MedicalServiceRequest::messages());
        $this->addToAssertionCount(1);
    }

    public function testMissingServiceIdThrows422(): void
    {
        $this->expectException(ValidationException::class);
        RequestValidator::validate([], MedicalServiceRequest::rules(), MedicalServiceRequest::messages());
    }

    public function testCustomDurationAboveLimitThrows422(): void
    {
        try {
            RequestValidator::validate(
                ['serviceId' => 1, 'customDurationMinutes' => 481],
                MedicalServiceRequest::rules(),
                MedicalServiceRequest::messages()
            );
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customDurationMinutes', $e->getErrors());
        }
    }

    public function testCustomDurationAtLimitPasses(): void
    {
        RequestValidator::validate(
            ['serviceId' => 1, 'customDurationMinutes' => 480],
            MedicalServiceRequest::rules(),
            MedicalServiceRequest::messages()
        );
        $this->addToAssertionCount(1);
    }

    public function testNegativeCustomPriceThrows422(): void
    {
        try {
            RequestValidator::validate(
                ['serviceId' => 1, 'customPrice' => -10],
                MedicalServiceRequest::rules(),
                MedicalServiceRequest::messages()
            );
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customPrice', $e->getErrors());
        }
    }
}

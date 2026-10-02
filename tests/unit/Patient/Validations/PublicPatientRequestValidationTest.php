<?php

use App\Dto\Request\Patient\PublicPatientRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicPatientRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'firstName' => 'Juana',
            'lastName'  => 'Pérez',
            'email'     => 'juana@example.com',
            'phone'     => '2325506499',
            'dni'       => '30123456',
        ];
    }

    private function validate(array $payload): void
    {
        RequestValidator::validate($payload, PublicPatientRequest::rules(), PublicPatientRequest::messages());
    }

    public function testValidPayloadPasses(): void
    {
        $this->validate($this->validPayload());
        $this->addToAssertionCount(1);
    }

    public function testSevenDigitDniPasses(): void
    {
        $payload = $this->validPayload();
        $payload['dni'] = '7123456';

        $this->validate($payload);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider missingFieldProvider
     */
    public function testEveryFieldIsRequired(string $field): void
    {
        $payload = $this->validPayload();
        unset($payload[$field]);

        try {
            $this->validate($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertArrayHasKey($field, $e->getErrors());
        }
    }

    public static function missingFieldProvider(): array
    {
        return [
            'nombre'   => ['firstName'],
            'apellido' => ['lastName'],
            'email'    => ['email'],
            'teléfono' => ['phone'],
            'dni'      => ['dni'],
        ];
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testInvalidValuesAreRejected(string $field, string $value): void
    {
        $payload = $this->validPayload();
        $payload[$field] = $value;

        try {
            $this->validate($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->getErrors());
        }
    }

    public static function invalidValueProvider(): array
    {
        return [
            'email inválido'         => ['email', 'no-es-un-email'],
            'email demasiado largo'  => ['email', str_repeat('a', 150) . '@example.com'],
            'teléfono con letras'    => ['phone', '2325abc499'],
            'teléfono corto'         => ['phone', '2325'],
            'teléfono sin normalizar' => ['phone', '2325-506499'],
            'dni corto'              => ['dni', '123456'],
            'dni largo'              => ['dni', '123456789'],
            'dni con letras'         => ['dni', '12ab5678'],
            'dni sin normalizar'     => ['dni', '23.525.064'],
            'nombre corto'           => ['firstName', 'J'],
        ];
    }

    public function testGettersExposeTheNormalizedData(): void
    {
        $request = new PublicPatientRequest('Juana', 'Pérez', 'juana@example.com', '2325506499', 30123456);

        $this->assertSame('Juana', $request->getFirstName());
        $this->assertSame('Pérez', $request->getLastName());
        $this->assertSame('juana@example.com', $request->getEmail());
        $this->assertSame('2325506499', $request->getPhone());
        $this->assertSame(30123456, $request->getDni());
    }
}

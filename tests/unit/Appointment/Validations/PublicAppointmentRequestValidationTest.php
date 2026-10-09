<?php

use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\PublicPatientRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicAppointmentRequestValidationTest extends CIUnitTestCase
{
    private function validPayload(): array
    {
        return [
            'firstName' => 'Juana',
            'lastName'  => 'Pérez',
            'email'     => 'juana@example.com',
            'phone'     => '2325506499',
            'dni'       => '30123456',
            'medicalId' => 1,
            'serviceId' => 2,
            'fecha'     => '2026-10-05',
            'hora'      => '09:30',
            'nota'      => 'Primera consulta',
        ];
    }

    private function validate(array $payload): void
    {
        RequestValidator::validate($payload, PublicAppointmentRequest::rules(), PublicAppointmentRequest::messages());
    }

    public function testValidPayloadPasses(): void
    {
        $this->validate($this->validPayload());
        $this->addToAssertionCount(1);
    }

    public function testNoteIsOptional(): void
    {
        $payload = $this->validPayload();
        unset($payload['nota']);

        $this->validate($payload);
        $this->addToAssertionCount(1);
    }

    public function testIncludesTheIdentityRules(): void
    {
        $this->assertSame(
            [],
            array_diff_key(PublicPatientRequest::rules(), PublicAppointmentRequest::rules())
        );
    }

    /**
     * @dataProvider missingFieldProvider
     */
    public function testAppointmentFieldsAreRequired(string $field): void
    {
        $payload = $this->validPayload();
        unset($payload[$field]);

        try {
            $this->validate($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->getErrors());
        }
    }

    public static function missingFieldProvider(): array
    {
        return [
            'médico'   => ['medicalId'],
            'servicio' => ['serviceId'],
            'fecha'    => ['fecha'],
            'hora'     => ['hora'],
        ];
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testInvalidValuesAreRejected(string $field, mixed $value): void
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
            'médico en cero'              => ['medicalId', 0],
            'médico no numérico'          => ['medicalId', 'abc'],
            'servicio negativo'           => ['serviceId', -1],
            'fecha sin ceros'             => ['fecha', '2026-10-5'],
            'fecha con otro formato'      => ['fecha', '05/10/2026'],
            'fecha inexistente'           => ['fecha', '2026-02-30'],
            'hora sin cero inicial'       => ['hora', '9:30'],
            'hora con segundos'           => ['hora', '09:30:00'],
            'hora fuera de rango'         => ['hora', '25:00'],
            'minutos fuera de rango'      => ['hora', '09:75'],
            'nota demasiado larga'        => ['nota', str_repeat('a', 501)],
        ];
    }

    public function testStatusAndPatientIdAreNotPartOfTheContract(): void
    {
        $rules = PublicAppointmentRequest::rules();

        $this->assertArrayNotHasKey('status', $rules);
        $this->assertArrayNotHasKey('patientId', $rules);
    }

    public function testGettersExposeTheData(): void
    {
        $patient = new PublicPatientRequest('Juana', 'Pérez', 'juana@example.com', '2325506499', 30123456);
        $request = new PublicAppointmentRequest($patient, 1, 2, '2026-10-05', '09:30', 'Nota');

        $this->assertSame($patient, $request->getPatient());
        $this->assertSame(1, $request->getMedicalId());
        $this->assertSame(2, $request->getServiceId());
        $this->assertSame('2026-10-05', $request->getFecha());
        $this->assertSame('09:30', $request->getHora());
        $this->assertSame('Nota', $request->getNota());
    }
}

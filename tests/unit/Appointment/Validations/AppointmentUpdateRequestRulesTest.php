<?php

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Validation\ValidationException;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;
use App\Services\Appointment\Validations\AppointmentAvailabilityValidatorService;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PUT /appointments/{id}: reglas de formato de fecha/hora; las presentes pero vacías las rechaza
 * AppointmentUpdateValidatorService (antes llegaban a reschedule() y daban 500). Ausentes = sin cambio.
 *
 * @internal
 */
final class AppointmentUpdateRequestRulesTest extends CIUnitTestCase
{
    private function validate(array $body): void
    {
        RequestValidator::validate($body, AppointmentUpdateRequest::rules(), AppointmentUpdateRequest::messages());
    }

    public static function invalidFormatProvider(): array
    {
        return [
            'hora espacios' => [['hora' => '  '], 'hora'],
            'hora inválida' => [['hora' => '9am'], 'hora'],
        ];
    }

    #[DataProvider('invalidFormatProvider')]
    public function testInvalidFechaHoraFormatIsRejectedWithFieldError(array $body, string $field): void
    {
        try {
            $this->validate($body);
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame([$field], array_keys($e->getErrors()));
        }
    }

    public function testFullBodyWithEmptyNotaStaysValid(): void
    {
        $this->validate([
            'medicalId' => 1, 'patientId' => 2, 'serviceId' => 3,
            'fecha' => '2026-11-02', 'hora' => '09:30', 'nota' => '', 'status' => 'pending',
        ]);
        $this->addToAssertionCount(1);
    }

    public function testPartialBodiesWithoutFechaHoraStayValid(): void
    {
        $this->validate(['status' => 'cancelled']);
        $this->validate([]);
        $this->validate(['hora' => '09:30:00']);
        $this->addToAssertionCount(3);
    }

    public static function blankScheduleProvider(): array
    {
        return [
            'fecha vacía' => ['', null, 'fecha'],
            'hora vacía'  => [null, '', 'hora'],
        ];
    }

    #[DataProvider('blankScheduleProvider')]
    public function testBlankFechaOrHoraIsRejectedByUpdateValidator(?string $fecha, ?string $hora, string $field): void
    {
        $appointment = new Appointment(
            1, 1, 1, 1,
            new DateTime('2026-11-02'), new DateTime('09:00'), new DateTime('09:30'),
            null, 'confirmed', new DateTime(), new DateTime()
        );
        $request = new AppointmentUpdateRequest(null, null, null, $fecha, $hora, null, null);

        try {
            (new AppointmentUpdateValidatorService())->validate($request, $appointment, 1);
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame([$field], array_keys($e->getErrors()));
        }
    }

    public function testExcludeAppointmentIdValidation(): void
    {
        $validator = new AppointmentAvailabilityValidatorService();
        $validator->validateExcludeAppointmentId(null);
        $validator->validateExcludeAppointmentId('7');

        foreach (['0', '-1', 'abc', '', '1.5'] as $invalid) {
            try {
                $validator->validateExcludeAppointmentId($invalid);
                $this->fail('Se esperaba ValidationException.');
            } catch (ValidationException $e) {
                $this->assertSame(['excludeAppointmentId'], array_keys($e->getErrors()));
            }
        }
    }
}

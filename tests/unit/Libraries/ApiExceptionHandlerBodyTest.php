<?php

use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\AppointmentInPastException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\PublicBookingLimitExceededException;
use App\Libraries\ApiExceptionHandler;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * El front de la reserva pública ramifica por `code`: estos valores son contrato y no deben cambiar.
 *
 * @internal
 */
final class ApiExceptionHandlerBodyTest extends CIUnitTestCase
{
    /**
     * @dataProvider codedExceptions
     */
    public function testExceptionWithErrorCodeExposesIt(Throwable $exception, string $code): void
    {
        $body = ApiExceptionHandler::bodyFor($exception, $exception->getMessage());

        $this->assertSame($code, $body['code']);
        $this->assertSame($exception->getMessage(), $body['error']);
    }

    public static function codedExceptions(): iterable
    {
        yield 'horario ocupado' => [new AppointmentAlreadyExistsException(), 'SLOT_TAKEN'];
        yield 'fecha pasada' => [new AppointmentInPastException(), 'PAST_DATE'];
        yield 'médico no disponible' => [new MedicalNotAvailableException(), 'MEDICAL_NOT_AVAILABLE'];
        yield 'tope de turnos' => [new PublicBookingLimitExceededException(3), 'BOOKING_LIMIT_REACHED'];
    }

    public function testExceptionWithoutErrorCodeHasNoCodeKey(): void
    {
        $body = ApiExceptionHandler::bodyFor(new Exception('x', 409), 'x');

        $this->assertSame(['error' => 'x'], $body);
    }
}

<?php

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppointmentUpdateValidatorServiceTest extends CIUnitTestCase
{
    private AppointmentUpdateValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AppointmentUpdateValidatorService();
        session()->destroy();
    }

    private function appointment(string $status, string $fecha = '2026-12-01'): Appointment
    {
        return new Appointment(
            1,
            10,
            20,
            30,
            new DateTime($fecha),
            new DateTime('09:00:00'),
            new DateTime('09:30:00'),
            null,
            $status,
            new DateTime(),
            null
        );
    }

    private function request(
        ?string $status = null,
        ?string $fecha = null,
        ?string $nota = null,
        ?int $medicalId = null,
        ?int $patientId = null,
        ?int $serviceId = null,
        ?string $hora = null
    ): AppointmentUpdateRequest {
        return new AppointmentUpdateRequest(
            medicalId: $medicalId,
            patientId: $patientId,
            serviceId: $serviceId,
            fecha: $fecha,
            hora: $hora,
            nota: $nota,
            status: $status
        );
    }

    public function testPatientRequestingCancelledPassesValidation(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->validator->validate($this->request(status: 'cancelled'), $this->appointment('pending'), 1);
        $this->addToAssertionCount(1);
    }

    public function testPatientRequestingNonCancelledStatusThrows403(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los pacientes solo pueden cancelar turnos');

        $this->validator->validate($this->request(status: 'confirmed'), $this->appointment('pending'), 1);
    }

    public function testPatientRequestingNoStatusThrows403(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);

        $this->validator->validate($this->request(nota: 'algo'), $this->appointment('pending'), 1);
    }

    public function testMedicalCanOnlyMarkCompleted(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los médicos solo pueden marcar el turno como completado');

        $this->validator->validate($this->request(status: 'confirmed'), $this->appointment('confirmed'), 1);
    }

    public function testMedicalCannotModifyOtherFields(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los médicos solo pueden modificar el estado del turno');

        $this->validator->validate($this->request(status: 'completed', nota: 'cambio'), $this->appointment('confirmed'), 1);
    }

    public function testMedicalMarkingCompletedPasses(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->validator->validate($this->request(status: 'completed'), $this->appointment('confirmed'), 1);
        $this->addToAssertionCount(1);
    }

    public function testCannotModifyCompletedAppointment(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('No se puede modificar un turno completado o cancelado');

        $this->validator->validate($this->request(nota: 'x'), $this->appointment('completed'), 1);
    }

    public function testCannotModifyCancelledAppointment(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);

        $this->validator->validate($this->request(nota: 'x'), $this->appointment('cancelled'), 1);
    }

    public function testDisallowedStatusTransitionThrows409(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('La transición de estado del turno no está permitida');

        // pending -> completed no está en la tabla de transiciones permitidas
        $this->validator->validate($this->request(status: 'completed'), $this->appointment('pending'), 1);
    }

    public function testPastFechaThrows409(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('No se puede modificar un turno con fecha pasada');

        $this->validator->validate($this->request(fecha: '2020-01-01'), $this->appointment('pending'), 1);
    }

    public function testAdminAllowedTransitionPasses(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->validator->validate($this->request(status: 'confirmed'), $this->appointment('pending'), 1);
        $this->addToAssertionCount(1);
    }
}

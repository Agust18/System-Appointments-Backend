<?php

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentNotModifiableException;
use App\Exception\Appointment\InvalidStatusTransitionException;
use App\Exception\Appointment\MedicalCanOnlyChangeStatusException;
use App\Exception\Appointment\MedicalCanOnlyCompleteException;
use App\Exception\Appointment\PastAppointmentCannotBeModifiedException;
use App\Exception\Appointment\PatientCanOnlyCancelException;
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

        $this->expectException(PatientCanOnlyCancelException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los pacientes solo pueden cancelar turnos');

        $this->validator->validate($this->request(status: 'confirmed'), $this->appointment('pending'), 1);
    }

    public function testPatientCancellingConfirmedAppointmentPasses(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->validator->validate($this->request(status: 'cancelled'), $this->appointment('confirmed'), 1);
        $this->addToAssertionCount(1);
    }

    public function testPatientCannotChangeOtherFieldsWhileCancelling(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->expectException(PatientCanOnlyCancelException::class);

        $this->validator->validate($this->request(status: 'cancelled', fecha: '2026-12-02', nota: 'x'), $this->appointment('pending'), 1);
    }

    public function testPatientCannotCancelAPastAppointment(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->expectException(PastAppointmentCannotBeModifiedException::class);

        $this->validator->validate($this->request(status: 'cancelled'), $this->appointment('confirmed', '2020-01-01'), 1);
    }

    public function testPatientRequestingNoStatusThrows403(): void
    {
        session()->set('roleId', Role::PATIENT);

        $this->expectException(PatientCanOnlyCancelException::class);
        $this->expectExceptionCode(403);

        $this->validator->validate($this->request(nota: 'algo'), $this->appointment('pending'), 1);
    }

    public function testMedicalCanOnlyCompleteOrCancel(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->expectException(MedicalCanOnlyCompleteException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los médicos solo pueden completar o cancelar el turno');

        $this->validator->validate($this->request(status: 'confirmed'), $this->appointment('confirmed'), 1);
    }

    public function testMedicalCannotModifyOtherFields(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->expectException(MedicalCanOnlyChangeStatusException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Los médicos solo pueden modificar el estado y la nota del turno');

        $this->validator->validate($this->request(status: 'completed', fecha: '2026-12-02'), $this->appointment('confirmed'), 1);
    }

    public function testMedicalCanCompleteWithNote(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->validator->validate($this->request(status: 'completed', nota: 'Control sin novedades'), $this->appointment('confirmed'), 1);
        $this->addToAssertionCount(1);
    }

    public function testMedicalCanCancelOwnAppointment(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->validator->validate($this->request(status: 'cancelled'), $this->appointment('confirmed'), 1);
        $this->addToAssertionCount(1);
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

        $this->expectException(AppointmentNotModifiableException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('No se puede modificar un turno completado o cancelado');

        $this->validator->validate($this->request(nota: 'x'), $this->appointment('completed'), 1);
    }

    public function testCannotModifyCancelledAppointment(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(AppointmentNotModifiableException::class);
        $this->expectExceptionCode(409);

        $this->validator->validate($this->request(nota: 'x'), $this->appointment('cancelled'), 1);
    }

    public function testDisallowedStatusTransitionThrows409(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('La transición de estado del turno no está permitida');

        // pending -> completed no está en la tabla de transiciones permitidas
        $this->validator->validate($this->request(status: 'completed'), $this->appointment('pending'), 1);
    }

    public function testPastFechaThrows409(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->expectException(PastAppointmentCannotBeModifiedException::class);
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

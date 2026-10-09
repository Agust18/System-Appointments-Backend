<?php

use App\Email\Appointment\ReceptionNewAppointmentEmailTemplate;
use App\Entity\Appointment\Projection\AppointmentProjection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ReceptionNewAppointmentEmailTemplateTest extends CIUnitTestCase
{
    private function projection(?string $phone, string $paciente = 'Juana Pérez'): AppointmentProjection
    {
        return new AppointmentProjection(
            id: 1,
            medicalId: 2,
            patientId: 3,
            serviceId: 4,
            medicoNombre: 'Dr. House',
            pacienteNombre: $paciente,
            servicioNombre: 'Consulta general',
            paciente_email: 'juana@test.com',
            fecha: new DateTime('2026-10-12'),
            hora: new DateTime('09:30:00'),
            nota: null,
            status: 'confirmed',
            paciente_telefono: $phone,
        );
    }

    public function testBodyIncludesAllAppointmentData(): void
    {
        $body = (new ReceptionNewAppointmentEmailTemplate())->render($this->projection('2325506499'));

        $this->assertStringContainsString('Nuevo turno agendado', $body);
        $this->assertStringContainsString('12/10/2026', $body);
        $this->assertStringContainsString('09:30', $body);
        $this->assertStringContainsString('Juana Pérez', $body);
        $this->assertStringContainsString('Dr. House', $body);
        $this->assertStringContainsString('Consulta general', $body);
        $this->assertStringContainsString('2325506499', $body);
    }

    public function testBodyShowsPlaceholderWhenPhoneIsMissing(): void
    {
        $body = (new ReceptionNewAppointmentEmailTemplate())->render($this->projection(null));

        $this->assertStringContainsString('No informado', $body);
    }

    public function testBodyEscapesHtml(): void
    {
        $body = (new ReceptionNewAppointmentEmailTemplate())->render($this->projection('1', '<script>alert(1)</script>'));

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }
}

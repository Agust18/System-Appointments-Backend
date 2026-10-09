<?php

use App\Dto\Request\Email\EmailRequest;
use App\Services\Appointment\Notification\AppointmentConfirmedNotificationService;
use App\Services\Appointment\Notification\PublicAppointmentCreatedNotificationService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * Avisos por email al crear un turno (confirmado y reserva pública). El envío se observa inyectando un spy en
 * el constructor, así que nunca se toca SMTP.
 *
 * @internal
 */
final class AppointmentNotificationServiceTest extends BookingServiceTestCase
{
    private const RECEPTION = 'recepcion@clinica.test';

    /** @var list<EmailRequest> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sent = [];
    }

    private function configureReception(?string $email, bool $enabled = true, ?string $replyTo = null, int $tenantId = 1): void
    {
        $this->db->table('tenant_settings')->where('tenant_id', $tenantId)->update([
            'reception_email'         => $email,
            'reception_email_enabled' => $enabled ? 1 : 0,
            'reply_to_email'          => $replyTo,
        ]);
    }

    private function spy(): callable
    {
        return function (EmailRequest $request): void {
            $this->sent[] = $request;
        };
    }

    private function publicCreated(?callable $sender = null): PublicAppointmentCreatedNotificationService
    {
        return new PublicAppointmentCreatedNotificationService($sender ?? $this->spy());
    }

    private function confirmed(): AppointmentConfirmedNotificationService
    {
        return new AppointmentConfirmedNotificationService($this->spy());
    }

    private function seedAppointmentForPatient(string $status = 'confirmed'): int
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService();
        $patientId = $this->seedPatient();
        $this->db->query(
            'UPDATE users SET phone = ? WHERE id = (SELECT user_id FROM patients WHERE id = ?)',
            ['2325506499', $patientId]
        );

        return $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00', $status);
    }

    /**
     * @return list<string>
     */
    private function subjects(): array
    {
        return array_map(static fn (EmailRequest $r): string => $r->getSubject(), $this->sent);
    }

    public function testPublicCreateNotifiesPatientAndReception(): void
    {
        $this->configureReception(self::RECEPTION);
        $id = $this->seedAppointmentForPatient();

        $this->publicCreated()->notify($id);

        $this->assertSame(['Tu turno fue creado y confirmado', 'Nuevo turno agendado'], $this->subjects());
        $this->assertNotSame(self::RECEPTION, $this->sent[0]->getTo());
        $this->assertSame(self::RECEPTION, $this->sent[1]->getTo());
        $this->assertStringContainsString('2325506499', $this->sent[1]->getBody());
    }

    public function testPublicCreateWithoutReceptionEmailOnlyNotifiesPatientAndLogsWarning(): void
    {
        $id = $this->seedAppointmentForPatient();

        $this->publicCreated()->notify($id);

        $this->assertSame(['Tu turno fue creado y confirmado'], $this->subjects());
        $this->assertLogged('warning', 'reception.email no está configurado: no se avisó a recepción del turno ' . $id . '.');
    }

    public function testPublicCreateWithReceptionDisabledOnlyNotifiesPatient(): void
    {
        $this->configureReception(self::RECEPTION, false);
        $id = $this->seedAppointmentForPatient();

        $this->publicCreated()->notify($id);

        $this->assertSame(['Tu turno fue creado y confirmado'], $this->subjects());
    }

    public function testReceptionIsReadFromItsOwnTenantSettings(): void
    {
        $this->db->table('tenants')->insert(['slug' => 'otro', 'name' => 'Otro', 'status' => 'active']);
        $otherId = $this->db->insertID();
        $this->db->table('tenant_settings')->insert(['tenant_id' => $otherId, 'name' => 'Otro', 'reception_email' => 'otra@clinica.test']);
        $this->configureReception(self::RECEPTION);
        $id = $this->seedAppointmentForPatient();

        $this->publicCreated()->notify($id);

        $this->assertSame(self::RECEPTION, $this->sent[1]->getTo());
        $this->db->query('DELETE FROM tenant_settings WHERE tenant_id = ?', [$otherId]);
        $this->db->query('DELETE FROM tenants WHERE id = ?', [$otherId]);
    }

    public function testEveryEmailCarriesTheTenantReplyToFallingBackToReception(): void
    {
        $this->configureReception(self::RECEPTION, true, 'respuestas@clinica.test');
        $id = $this->seedAppointmentForPatient();
        $this->publicCreated()->notify($id);
        $this->assertSame(['respuestas@clinica.test', 'respuestas@clinica.test'], array_map(static fn (EmailRequest $r) => $r->getReplyTo(), $this->sent));

        $this->sent = [];
        $this->configureReception(self::RECEPTION);
        $this->confirmed()->notify($id);
        $this->assertSame([self::RECEPTION], array_map(static fn (EmailRequest $r) => $r->getReplyTo(), $this->sent));

        $this->sent = [];
        $this->configureReception(null);
        $this->confirmed()->notify($id);
        $this->assertSame([null], array_map(static fn (EmailRequest $r) => $r->getReplyTo(), $this->sent));
    }

    public function testPublicCreateIgnoresNonConfirmedAppointments(): void
    {
        $this->configureReception(self::RECEPTION);
        $id = $this->seedAppointmentForPatient('pending');

        $this->publicCreated()->notify($id);

        $this->assertSame([], $this->sent);
    }

    public function testFailureSendingToPatientDoesNotPreventReceptionEmail(): void
    {
        $this->configureReception(self::RECEPTION);
        $id = $this->seedAppointmentForPatient();
        $attempts = [];

        $this->publicCreated(function (EmailRequest $request) use (&$attempts): void {
            $attempts[] = $request->getSubject();
            if ($request->getSubject() === 'Tu turno fue creado y confirmado') {
                throw new RuntimeException('SMTP caído');
            }
        })->notify($id);

        $this->assertSame(['Tu turno fue creado y confirmado', 'Nuevo turno agendado'], $attempts);
    }

    public function testStandardCreateKeepsOriginalBehaviourWithoutReceptionEmail(): void
    {
        $this->configureReception(self::RECEPTION);
        $id = $this->seedAppointmentForPatient();

        $this->confirmed()->notify($id);

        $this->assertSame(['Tu turno fue confirmado'], $this->subjects());
        $this->assertNotSame(self::RECEPTION, $this->sent[0]->getTo());
    }
}

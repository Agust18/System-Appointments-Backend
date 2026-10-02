<?php

use App\Dto\Request\Email\EmailRequest;
use App\Services\Appointment\AppointmentNotificationService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * Avisos por email al crear un turno. El envío se observa inyectando un spy en
 * el constructor, así que nunca se toca SMTP.
 *
 * @internal
 */
final class AppointmentNotificationServiceTest extends BookingServiceTestCase
{
    private const RECEPTION = 'recepcion@clinica.test';

    /** @var list<EmailRequest> */
    private array $sent = [];

    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sent = [];
        foreach (['reception.email', 'reception.emailEnabled'] as $key) {
            $this->previousEnv[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $key => [$env, $server]) {
            unset($_ENV[$key], $_SERVER[$key]);
            if ($env !== null) {
                $_ENV[$key] = $env;
            }
            if ($server !== null) {
                $_SERVER[$key] = $server;
            }
        }

        parent::tearDown();
    }

    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }

    private function service(?callable $sender = null): AppointmentNotificationService
    {
        return new AppointmentNotificationService($sender ?? function (EmailRequest $request): void {
            $this->sent[] = $request;
        });
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
        $this->setEnv('reception.email', self::RECEPTION);
        $id = $this->seedAppointmentForPatient();

        $this->service()->notifyOnPublicCreate($id);

        $this->assertSame(['Tu turno fue creado y confirmado', 'Nuevo turno agendado'], $this->subjects());
        $this->assertNotSame(self::RECEPTION, $this->sent[0]->getTo());
        $this->assertSame(self::RECEPTION, $this->sent[1]->getTo());
        $this->assertStringContainsString('2325506499', $this->sent[1]->getBody());
    }

    public function testPublicCreateWithoutReceptionEmailOnlyNotifiesPatientAndLogsWarning(): void
    {
        $id = $this->seedAppointmentForPatient();

        $this->service()->notifyOnPublicCreate($id);

        $this->assertSame(['Tu turno fue creado y confirmado'], $this->subjects());
        $this->assertLogged('warning', 'reception.email no está configurado: no se avisó a recepción del turno ' . $id . '.');
    }

    public function testPublicCreateWithReceptionDisabledOnlyNotifiesPatient(): void
    {
        $this->setEnv('reception.email', self::RECEPTION);
        $this->setEnv('reception.emailEnabled', 'false');
        $id = $this->seedAppointmentForPatient();

        $this->service()->notifyOnPublicCreate($id);

        $this->assertSame(['Tu turno fue creado y confirmado'], $this->subjects());
    }

    public function testPublicCreateIgnoresNonConfirmedAppointments(): void
    {
        $this->setEnv('reception.email', self::RECEPTION);
        $id = $this->seedAppointmentForPatient('pending');

        $this->service()->notifyOnPublicCreate($id);

        $this->assertSame([], $this->sent);
    }

    public function testFailureSendingToPatientDoesNotPreventReceptionEmail(): void
    {
        $this->setEnv('reception.email', self::RECEPTION);
        $id = $this->seedAppointmentForPatient();
        $attempts = [];

        $this->service(function (EmailRequest $request) use (&$attempts): void {
            $attempts[] = $request->getSubject();
            if ($request->getSubject() === 'Tu turno fue creado y confirmado') {
                throw new RuntimeException('SMTP caído');
            }
        })->notifyOnPublicCreate($id);

        $this->assertSame(['Tu turno fue creado y confirmado', 'Nuevo turno agendado'], $attempts);
    }

    public function testStandardCreateKeepsOriginalBehaviourWithoutReceptionEmail(): void
    {
        $this->setEnv('reception.email', self::RECEPTION);
        $id = $this->seedAppointmentForPatient();

        $this->service()->notifyOnCreate($id);

        $this->assertSame(['Tu turno fue confirmado'], $this->subjects());
        $this->assertNotSame(self::RECEPTION, $this->sent[0]->getTo());
    }
}

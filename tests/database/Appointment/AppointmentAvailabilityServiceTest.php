<?php

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Exception\Validation\ValidationException;
use App\Services\Appointment\AppointmentAvailabilityService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test de regresión: fija el comportamiento ACTUAL de
 * AppointmentAvailabilityService antes de extraer ServiceDurationCalculatorService
 * y SlotGeneratorService, para garantizar que el refactor no cambia el output.
 *
 * @internal
 */
final class AppointmentAvailabilityServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null; // corre TODAS las migraciones (App incluida), no solo Tests\Support

    private AppointmentAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentAvailabilityService();
    }

    private function seedMedicalWithService(int $durationMinutes, int $bufferMinutes, ?int $customDurationMinutes = null): array
    {
        $roleId = $this->db->table('roles')->insert(['name' => 'medical_role_' . uniqid()]) ? $this->db->insertID() : null;

        $this->db->table('users')->insert([
            'first_name' => 'medico_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
            'role_id'  => $roleId,
        ]);
        $medicalUserId = $this->db->insertID();

        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'user_id'      => $medicalUserId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('services')->insert([
            'name'              => 'Servicio ' . uniqid(),
            'duration_minutes'  => $durationMinutes,
            'buffer_minutes'    => $bufferMinutes,
            'is_active'         => 1,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medical_service')->insert([
            'medical_id'              => $medicalId,
            'service_id'              => $serviceId,
            'custom_duration_minutes' => $customDurationMinutes,
            'is_active'               => 1,
        ]);

        return [$medicalId, $serviceId];
    }

    private function seedSchedule(int $medicalId, int $dayOfWeek, string $startTime, string $endTime): void
    {
        $this->db->table('medical_schedules')->insert([
            'medical_id'  => $medicalId,
            'day_of_week' => $dayOfWeek,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'is_active'   => 1,
        ]);
    }

    private function seedAppointment(int $medicalId, string $fecha, string $hora, string $horaFin): void
    {
        $this->db->table('users')->insert([
            'first_name' => 'paciente_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);
        $patientUserId = $this->db->insertID();

        $this->db->table('patients')->insert([
            'user_id'    => $patientUserId,
            'birth_date' => '1990-01-01',
        ]);
        $patientId = $this->db->insertID();

        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => $fecha,
            'hora'       => $hora,
            'hora_fin'   => $horaFin,
            'status'     => 'confirmed',
        ]);
    }

    public function testReturnsSlotsForAttendedDayExcludingExistingAppointment(): void
    {
        // 30 min de duración, sin buffer, step de 30 min -> slots: 09:00, 09:30, 10:00, 10:30
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);

        $fecha = '2026-10-05'; // se fija el día de la semana dinámicamente
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $this->seedSchedule($medicalId, $dayOfWeek, '09:00:00', '11:00:00');

        // bloquea el slot de 09:30
        $this->seedAppointment($medicalId, $fecha, '09:30:00', '10:00:00');

        $slots = $this->service->getAvailableSlots(new AppointmentAvailabilityRequest($medicalId, $serviceId, $fecha));

        $startTimes = array_column($slots, 'start_time');
        $this->assertSame(['09:00', '10:00', '10:30'], $startTimes);

        // formato completo del primer slot
        $this->assertSame(
            ['start_time' => '09:00', 'end_time' => '09:30', 'block_end_time' => '09:30'],
            $slots[0]
        );
    }

    public function testUsesCustomDurationOverrideAndBuffer(): void
    {
        // duración base 30, override del médico a 20, buffer 10 -> bloque total 30
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 10, 20);

        $fecha = '2026-10-06';
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $this->seedSchedule($medicalId, $dayOfWeek, '09:00:00', '10:00:00');

        $slots = $this->service->getAvailableSlots(new AppointmentAvailabilityRequest($medicalId, $serviceId, $fecha));

        $this->assertSame(
            ['start_time' => '09:00', 'end_time' => '09:20', 'block_end_time' => '09:30'],
            $slots[0]
        );
    }

    public function testReturnsEmptyArrayForDayNotAttended(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);

        $fecha = '2026-10-07';
        $dayOfWeek = (int) date('N', strtotime($fecha));
        // agenda un día distinto al de la fecha consultada
        $otroDia = $dayOfWeek === 7 ? 1 : $dayOfWeek + 1;
        $this->seedSchedule($medicalId, $otroDia, '09:00:00', '10:00:00');

        $slots = $this->service->getAvailableSlots(new AppointmentAvailabilityRequest($medicalId, $serviceId, $fecha));

        $this->assertSame([], $slots);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFechaProvider(): array
    {
        return [
            'formato libre'             => ['05/10/2026'],
            'sin ceros'                 => ['2026-1-5'],
            'vacía'                     => [''],
            'día inexistente (febrero)' => ['2026-02-30'],
            'mes inexistente'           => ['2026-13-01'],
        ];
    }

    #[DataProvider('invalidFechaProvider')]
    public function testInvalidFechaThrowsValidationExceptionWithFechaError(string $fecha): void
    {
        // la fecha se valida antes de consultar servicio o médico, no hace falta sembrar datos
        try {
            $this->service->getAvailableSlots(new AppointmentAvailabilityRequest(1, 1, $fecha));
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame(['fecha'], array_keys($e->getErrors()));
            $this->assertSame('El formato de fecha debe ser YYYY-MM-DD', $e->getErrors()['fecha']);
        }
    }
}

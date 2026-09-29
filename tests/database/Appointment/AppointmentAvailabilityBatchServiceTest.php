<?php

use App\Dto\Request\Appointment\AppointmentAvailabilityBatchRequest;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Validation\ValidationException;
use App\Services\Appointment\AppointmentAvailabilityBatchService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class AppointmentAvailabilityBatchServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null; // corre TODAS las migraciones (App incluida), no solo Tests\Support

    private AppointmentAvailabilityBatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentAvailabilityBatchService();
    }

    private function seedMedicalWithService(int $durationMinutes, int $bufferMinutes, int $slotStepMinutes): array
    {
        $this->db->table('users')->insert([
            'first_name' => 'medico_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'      => uniqid() . '@test.com',
            'password'   => 'hash',
            'dni'        => random_int(1000000, 9999999),
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
            'slot_step_minutes' => $slotStepMinutes,
            'is_active'         => 1,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medical_service')->insert([
            'medical_id' => $medicalId,
            'service_id' => $serviceId,
            'is_active'  => 1,
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
            'email'      => uniqid() . '@test.com',
            'password'   => 'hash',
            'dni'        => random_int(1000000, 9999999),
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

    public function testFourteenDayRangeOnlyIncludesAttendedDaysAndExcludesOverlaps(): void
    {
        // 30 min de duración, sin buffer, step de 30 min -> por día: 09:00, 09:30, 10:00
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, 30);

        $from = '2026-11-02'; // lunes
        $to = '2026-11-15';   // domingo siguiente (14 días)

        $lunes = (int) date('N', strtotime($from));
        $this->seedSchedule($medicalId, $lunes, '09:00:00', '10:30:00');

        // bloquea el slot de 09:30 del primer lunes
        $this->seedAppointment($medicalId, $from, '09:30:00', '10:00:00');

        $response = $this->service->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, $from, $to));

        // solo deberían aparecer los 2 lunes del rango (únicos días atendidos)
        $this->assertSame(['2026-11-02', '2026-11-09'], array_keys($response->data));

        // primer lunes: turno existente bloquea 09:30
        $this->assertSame(['09:00', '10:00'], $response->data['2026-11-02']);

        // segundo lunes: sin turnos, todos los slots libres
        $this->assertSame(['09:00', '09:30', '10:00'], $response->data['2026-11-09']);
    }

    public function testAttendedDayWithoutFreeSlotsAppearsAsEmptyArray(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(60, 0, 60);

        $from = '2026-11-02';
        $to = '2026-11-03';
        $dayOfWeek = (int) date('N', strtotime($from));
        $this->seedSchedule($medicalId, $dayOfWeek, '09:00:00', '10:00:00');

        // ocupa el único slot posible del día
        $this->seedAppointment($medicalId, $from, '09:00:00', '10:00:00');

        $response = $this->service->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, $from, $to));

        $this->assertArrayHasKey($from, $response->data);
        $this->assertSame([], $response->data[$from]);
    }

    public function testRejectsRangeOver60Days(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, 30);

        $this->expectException(ValidationException::class);
        $this->service->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, '2026-01-01', '2026-12-31'));
    }

    public function testRejectsFromAfterTo(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, 30);

        $this->expectException(ValidationException::class);
        $this->service->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, '2026-11-15', '2026-11-02'));
    }

    public function testThrowsWhenMedicalDoesNotOfferService(): void
    {
        $this->db->table('users')->insert([
            'first_name' => 'medico_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'      => uniqid() . '@test.com',
            'password'   => 'hash',
            'dni'        => random_int(1000000, 9999999),
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
            'name'             => 'Servicio ' . uniqid(),
            'duration_minutes' => 30,
            'is_active'        => 1,
        ]);
        $serviceId = $this->db->insertID();
        // sin fila en medical_service: el médico no ofrece este servicio

        $this->expectException(MedicalServiceNotOfferedException::class);
        $this->service->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, '2026-11-02', '2026-11-03'));
    }
}

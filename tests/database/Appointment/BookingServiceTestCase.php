<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Base común de los tests de caracterización de AppointmentBookingService y
 * AppointmentUpdateBookingService. Solo aporta helpers de seed y asserts de
 * lock/rollback; no contiene tests propios.
 */
abstract class BookingServiceTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null; // corre TODAS las migraciones (App incluida)

    protected const FECHA = '2026-10-05';

    /**
     * @return array{0:int,1:int} [medicalId, serviceId]
     */
    protected function seedMedicalWithService(
        int $durationMinutes = 30,
        int $bufferMinutes = 0,
        ?int $customDurationMinutes = null,
        bool $serviceActive = true,
        bool $offered = true
    ): array {
        $medicalId = $this->seedMedical();
        $serviceId = $this->seedService($durationMinutes, $bufferMinutes, $serviceActive);

        if ($offered) {
            $this->offerService($medicalId, $serviceId, $customDurationMinutes);
        }

        return [$medicalId, $serviceId];
    }

    protected function seedMedical(): int
    {
        $this->db->table('roles')->insert(['name' => 'medical_role_' . uniqid()]);
        $roleId = $this->db->insertID();

        $this->db->table('users')->insert([
            'first_name' => 'medico_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'      => uniqid() . '@test.com',
            'password'   => 'hash',
            'dni'        => random_int(1000000, 9999999),
            'role_id'    => $roleId,
        ]);
        $userId = $this->db->insertID();

        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return (int) $this->db->insertID();
    }

    protected function seedService(int $durationMinutes, int $bufferMinutes, bool $active = true): int
    {
        $this->db->table('services')->insert([
            'name'              => 'Servicio ' . uniqid(),
            'duration_minutes'  => $durationMinutes,
            'buffer_minutes'    => $bufferMinutes,
            'slot_step_minutes' => 30,
            'is_active'         => $active ? 1 : 0,
        ]);

        return (int) $this->db->insertID();
    }

    protected function offerService(int $medicalId, int $serviceId, ?int $customDurationMinutes = null): void
    {
        $this->db->table('medical_service')->insert([
            'medical_id'              => $medicalId,
            'service_id'              => $serviceId,
            'custom_duration_minutes' => $customDurationMinutes,
            'is_active'               => 1,
        ]);
    }

    protected function seedPatient(): int
    {
        $this->db->table('users')->insert([
            'first_name' => 'paciente_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'      => uniqid() . '@test.com',
            'password'   => 'hash',
            'dni'        => random_int(1000000, 9999999),
        ]);
        $userId = $this->db->insertID();

        $this->db->table('patients')->insert([
            'user_id'    => $userId,
            'birth_date' => '1990-01-01',
        ]);

        return (int) $this->db->insertID();
    }

    protected function seedAppointment(
        int $medicalId,
        int $patientId,
        int $serviceId,
        string $hora,
        string $horaFin,
        string $status = 'confirmed'
    ): int {
        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => self::FECHA,
            'hora'       => $hora,
            'hora_fin'   => $horaFin,
            'status'     => $status,
        ]);

        return (int) $this->db->insertID();
    }

    protected function countAppointments(): int
    {
        return (int) $this->db->table('appointments')->countAllResults();
    }

    protected function assertLockReleased(int $medicalId, string $fecha): void
    {
        $lockName = sprintf('appt_lock_%d_%s', $medicalId, $fecha);
        $row = $this->db->query('SELECT IS_FREE_LOCK(?) AS is_free', [$lockName])->getRow();

        $this->assertSame(1, (int) $row->is_free, 'El advisory lock debe quedar liberado.');
    }
}

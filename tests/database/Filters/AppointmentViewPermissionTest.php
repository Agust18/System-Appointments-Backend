<?php

use App\Constants\Role;
use App\Filters\AppointmentViewPermission;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AppointmentViewPermissionTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private AppointmentViewPermission $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new AppointmentViewPermission();
        session()->destroy();
    }

    private function seedUser(): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'user_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);

        return $this->db->insertID();
    }

    private function seedMedical(int $userId): int
    {
        $this->db->table('specialties')->insert(['tenant_id' => 1, 'name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert(['tenant_id' => 1,
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function seedPatient(int $userId): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1,
            'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com', 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999),
            'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    private function seedAppointment(int $medicalId, int $patientId, string $status = 'pending'): int
    {
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => '2026-10-05',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => $status,
        ]);

        return $this->db->insertID();
    }

    private function requestFor(string $path): \CodeIgniter\HTTP\RequestInterface
    {
        $request = Services::request(null, false);
        $request->getUri()->setPath($path);

        return $request;
    }

    public function testAdminCanViewAnyAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId);

        session()->set('roleId', Role::ADMIN);
        session()->set('userId', 999);

        $result = $this->filter->before($this->requestFor("appointments/{$appointmentId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }



    public function testMedicalCanViewOwnAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("appointments/{$appointmentId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testUnknownAppointmentReturns404(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 1);

        $result = $this->filter->before($this->requestFor('appointments/999999'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }
}

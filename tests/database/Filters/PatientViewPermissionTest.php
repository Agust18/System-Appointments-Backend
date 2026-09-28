<?php

use App\Constants\Role;
use App\Filters\PatientViewPermission;
use App\Models\Appointment\AppointmentModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class PatientViewPermissionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientViewPermission $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new PatientViewPermission();
        session()->destroy();
    }

    private function seedUser(): int
    {
        $this->db->table('users')->insert([
            'username' => 'user_' . uniqid(),
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);

        return $this->db->insertID();
    }

    private function seedMedical(int $userId): int
    {
        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function seedPatient(int $userId): int
    {
        $this->db->table('patients')->insert([
            'user_id'    => $userId,
            'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    private function seedAppointment(int $medicalId, int $patientId): void
    {
        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => '2026-10-05',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'confirmed',
        ]);
    }

    private function requestFor(string $path): \CodeIgniter\HTTP\RequestInterface
    {
        $request = Services::request(null, false);
        $request->getUri()->setPath($path);

        return $request;
    }

    public function testMedicalWithAppointmentCanViewPatient(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->seedAppointment($medicalId, $patientId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("patients/{$patientId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalWithoutAppointmentCannotViewPatient(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("patients/{$patientId}"));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testPatientCanViewOwnProfile(): void
    {
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $patientUserId);

        $result = $this->filter->before($this->requestFor("patients/{$patientId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testPatientCannotViewOtherPatientProfile(): void
    {
        $ownUserId = $this->seedUser();
        $this->seedPatient($ownUserId);

        $otherUserId = $this->seedUser();
        $otherPatientId = $this->seedPatient($otherUserId);

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $ownUserId);

        $result = $this->filter->before($this->requestFor("patients/{$otherPatientId}"));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testUnknownPatientReturns404(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor('patients/999999'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }

    public function testHasRelationWithPatientMatchesFilterBehavior(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $model = new AppointmentModel();
        $this->assertFalse($model->hasRelationWithPatient($medicalUserId, $patientId));

        $this->seedAppointment($medicalId, $patientId);

        $this->assertTrue($model->hasRelationWithPatient($medicalUserId, $patientId));
    }

    public function testPatientCanViewOwnProfileByUserId(): void
    {
        $patientUserId = $this->seedUser();
        $this->seedPatient($patientUserId);

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $patientUserId);

        $result = $this->filter->before($this->requestFor("patients/user/{$patientUserId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalWithAppointmentCanViewPatientByUserId(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->seedAppointment($medicalId, $patientId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("patients/user/{$patientUserId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testUnknownPatientByUserIdReturns404(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 1);

        $result = $this->filter->before($this->requestFor('patients/user/999999'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }
}

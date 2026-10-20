<?php

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Models\Patient\PatientModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Cubre el filtro EXISTS por medicalUserId agregado a searchProjection()/
 * countProjection() para que un médico solo vea pacientes con los que tuvo
 * al menos un turno.
 *
 * @internal
 */
final class PatientModelMedicalFilterTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PatientModel();
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

    private function seedAppointment(int $medicalId, int $patientId): void
    {
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => '2026-10-05',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'pending',
        ]);
    }

    private function filterRequest(?int $medicalUserId): PatientFilterRequest
    {
        return new PatientFilterRequest(new PaginationRequest(1, 10), null, $medicalUserId);
    }

    public function testSearchProjectionOnlyReturnsRelatedPatientsForMedical(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $relatedPatientUserId = $this->seedUser();
        $relatedPatientId = $this->seedPatient($relatedPatientUserId);
        $this->seedAppointment($medicalId, $relatedPatientId);

        $unrelatedPatientUserId = $this->seedUser();
        $this->seedPatient($unrelatedPatientUserId);

        $results = $this->model->searchProjection($this->filterRequest($medicalUserId));
        $ids = array_map(fn ($p) => $p->getId(), $results);

        $this->assertContains($relatedPatientId, $ids);
        $this->assertCount(1, $results);
    }

    public function testCountProjectionOnlyCountsRelatedPatientsForMedical(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $relatedPatientUserId = $this->seedUser();
        $relatedPatientId = $this->seedPatient($relatedPatientUserId);
        $this->seedAppointment($medicalId, $relatedPatientId);

        $unrelatedPatientUserId = $this->seedUser();
        $this->seedPatient($unrelatedPatientUserId);

        $count = $this->model->countProjection($this->filterRequest($medicalUserId));

        $this->assertSame(1, $count);
    }

    public function testNoMedicalUserIdReturnsAllPatients(): void
    {
        $patientUserIdA = $this->seedUser();
        $this->seedPatient($patientUserIdA);

        $patientUserIdB = $this->seedUser();
        $this->seedPatient($patientUserIdB);

        $results = $this->model->searchProjection($this->filterRequest(null));

        $this->assertCount(2, $results);
    }

    public function testMedicalWithNoAppointmentsSeesNoPatients(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        $otherPatientUserId = $this->seedUser();
        $this->seedPatient($otherPatientUserId);

        $results = $this->model->searchProjection($this->filterRequest($medicalUserId));

        $this->assertCount(0, $results);
    }
}

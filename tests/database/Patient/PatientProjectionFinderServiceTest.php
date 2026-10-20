<?php

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Exception\Patient\PatientNotFoundException;
use App\Models\Patient\PatientModel;
use App\Services\Patient\Projection\PatientProjectionFinderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Cubre el agregado de `email` a PatientProjection: el converter es
 * compartido entre findProjection() (detalle) y searchProjection()
 * (listado), así que se verifican ambos caminos para asegurar que el
 * listado no se rompió al agregar la columna.
 *
 * @internal
 */
final class PatientProjectionFinderServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientProjectionFinderService $service;
    private PatientModel $patientModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientProjectionFinderService();
        $this->patientModel = new PatientModel();
    }

    private function seedPatient(string $email): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1,
            'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => $email, 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999),
            'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    public function testFindResponseIncludesPatientEmail(): void
    {
        $email = uniqid() . '@test.com';
        $patientId = $this->seedPatient($email);

        $response = $this->service->findResponse($patientId);

        $this->assertSame($email, $response->email);
    }

    public function testFindThrowsNotFoundExceptionForMissingId(): void
    {
        $this->expectException(PatientNotFoundException::class);
        $this->service->find(999999);
    }

    public function testSearchProjectionStillWorksAfterAddingEmailColumn(): void
    {
        $email = uniqid() . '@test.com';
        $this->seedPatient($email);

        $request = new PatientFilterRequest(new PaginationRequest(1, 10));
        $results = $this->patientModel->searchProjection($request);

        $this->assertNotEmpty($results);
        $match = current(array_filter($results, fn ($p) => $p->getEmail() === $email));
        $this->assertNotFalse($match, 'El paciente sembrado debe aparecer en el listado con su email');
        $this->assertNotSame('', $match->getPacienteNombre());
    }
}

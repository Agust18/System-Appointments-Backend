<?php

use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Exception\Medical\MedicalNotFoundException;
use App\Models\Medical\MedicalModel;
use App\Services\Medical\Projection\MedicalProjectionFinderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Cubre el agregado de `email` a MedicalProjection: el converter es
 * compartido entre findProjection() (detalle), searchProjection() (listado)
 * y searchAvailableProjection() (médicos disponibles), así que se verifican
 * los tres caminos para asegurar que ninguno se rompió al agregar la
 * columna.
 *
 * @internal
 */
final class MedicalProjectionFinderServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private MedicalProjectionFinderService $service;
    private MedicalModel $medicalModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MedicalProjectionFinderService();
        $this->medicalModel = new MedicalModel();
    }

    private function seedMedical(string $email): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'medico_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => $email,
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);
        $userId = $this->db->insertID();

        $this->db->table('specialties')->insert(['tenant_id' => 1, 'name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert(['tenant_id' => 1,
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
            'is_active'    => 1,
        ]);

        return $this->db->insertID();
    }

    public function testFindResponseIncludesUserEmail(): void
    {
        $email = uniqid() . '@test.com';
        $medicalId = $this->seedMedical($email);

        $response = $this->service->findResponse($medicalId);

        $this->assertSame($email, $response->email);
    }

    public function testFindThrowsNotFoundExceptionForMissingId(): void
    {
        $this->expectException(MedicalNotFoundException::class);
        $this->service->find(999999);
    }

    public function testSearchProjectionStillWorksAfterAddingEmailColumn(): void
    {
        $email = uniqid() . '@test.com';
        $this->seedMedical($email);

        $request = new MedicalFilterRequest(new PaginationRequest(1, 10));
        $results = $this->medicalModel->searchProjection($request);

        $this->assertNotEmpty($results);
        $match = current(array_filter($results, fn ($m) => $m->getEmail() === $email));
        $this->assertNotFalse($match, 'El médico sembrado debe aparecer en el listado con su email');
        $this->assertNotSame('', $match->getMedicoNombre());
    }

    public function testSearchAvailableProjectionStillWorksAfterAddingEmailColumn(): void
    {
        $email = uniqid() . '@test.com';
        $this->seedMedical($email);

        $request = new MedicalFilterRequest(new PaginationRequest(1, 10));
        $results = $this->medicalModel->searchAvailableProjection($request);

        $this->assertNotEmpty($results);
        $match = current(array_filter($results, fn ($m) => $m->getEmail() === $email));
        $this->assertNotFalse($match, 'El médico activo sembrado debe aparecer con su email');
    }
}

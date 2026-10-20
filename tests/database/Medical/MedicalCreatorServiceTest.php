<?php

use App\Constants\Role;
use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Medical\MedicalCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class MedicalCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private MedicalCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MedicalCreatorService();
    }

    private function userRequest(?int $roleId): UserRequest
    {
        return new UserRequest(
            'user_' . uniqid(),
            'Apellido',
            uniqid() . '@test.com',
            null,
            'password123',
            random_int(1000000, 9999999),
            true,
            $roleId
        );
    }

    private function medicalDataRequest(): MedicalDataRequest
    {
        $this->db->table('specialties')->insert(['tenant_id' => 1, 'name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        return new MedicalDataRequest('MAT-' . uniqid(), $specialtyId);
    }

    public function testMissingRoleIdThrows400AndCreatesNoUser(): void
    {
        $request = new MedicalRequest($this->userRequest(null), $this->medicalDataRequest());

        try {
            $this->service->create($request);
            $this->fail('Se esperaba una excepción');
        } catch (Exception $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testMismatchedRoleIdThrows403AndCreatesNoUser(): void
    {
        $request = new MedicalRequest($this->userRequest(Role::RECEPTION), $this->medicalDataRequest());

        try {
            $this->service->create($request);
            $this->fail('Se esperaba una excepción');
        } catch (Exception $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testCorrectRoleIdCreatesUserAndMedical(): void
    {
        $this->db->table('roles')->insert(['id' => Role::MEDICAL, 'name' => 'medical']);

        $request = new MedicalRequest($this->userRequest(Role::MEDICAL), $this->medicalDataRequest());

        $response = $this->service->create($request);

        $this->assertNotNull($response);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'medicals', []);
    }
}

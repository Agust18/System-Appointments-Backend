<?php

use App\Constants\Role;
use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalQuickRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Medical\MedicalQuickCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MedicalQuickCreatorServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private MedicalQuickCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MedicalQuickCreatorService();
    }

    private function userRequest(?int $roleId): UserRequest
    {
        return new UserRequest(
            'user_' . uniqid(),
            uniqid() . '@test.com',
            'password123',
            random_int(1000000, 9999999),
            true,
            $roleId
        );
    }

    private function medicalDataRequest(): MedicalDataRequest
    {
        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        return new MedicalDataRequest('MAT-' . uniqid(), $specialtyId);
    }

    public function testMissingRoleIdThrows400AndCreatesNoUser(): void
    {
        $request = new MedicalQuickRequest($this->userRequest(null), $this->medicalDataRequest());

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
        $request = new MedicalQuickRequest($this->userRequest(Role::PATIENT), $this->medicalDataRequest());

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

        $request = new MedicalQuickRequest($this->userRequest(Role::MEDICAL), $this->medicalDataRequest());

        $response = $this->service->create($request);

        $this->assertNotNull($response);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'medicals', []);
    }
}

<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientQuickRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientQuickCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PatientQuickCreatorServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientQuickCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientQuickCreatorService();
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

    private function patientDataRequest(): PatientDataRequest
    {
        return new PatientDataRequest('1990-01-01');
    }

    public function testMissingRoleIdThrows400AndCreatesNoUser(): void
    {
        $request = new PatientQuickRequest($this->userRequest(null), $this->patientDataRequest());

        try {
            $this->service->create($request, true);
            $this->fail('Se esperaba una excepción');
        } catch (Exception $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testMismatchedRoleIdThrows403AndCreatesNoUser(): void
    {
        $request = new PatientQuickRequest($this->userRequest(Role::MEDICAL), $this->patientDataRequest());

        try {
            $this->service->create($request, true);
            $this->fail('Se esperaba una excepción');
        } catch (Exception $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
    }

    public function testCorrectRoleIdCreatesUserAndPatient(): void
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $request = new PatientQuickRequest($this->userRequest(Role::PATIENT), $this->patientDataRequest());

        $response = $this->service->create($request, true);

        $this->assertNotNull($response);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
    }
}

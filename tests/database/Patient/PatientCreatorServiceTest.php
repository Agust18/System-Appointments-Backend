<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PatientCreatorServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientCreatorService();
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

    private function patientDataRequest(): PatientDataRequest
    {
        return new PatientDataRequest('1990-01-01');
    }

    public function testMissingRoleIdThrows400AndCreatesNoUser(): void
    {
        $request = new PatientRequest($this->userRequest(null), $this->patientDataRequest());

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
        $request = new PatientRequest($this->userRequest(Role::MEDICAL), $this->patientDataRequest());

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

        $request = new PatientRequest($this->userRequest(Role::PATIENT), $this->patientDataRequest());

        $response = $this->service->create($request, true);

        $this->assertNotNull($response);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
    }

    public function testDbTransactionRollsBackOnFailure(): void
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $futureBirthDate = (new DateTime('+1 day'))->format('Y-m-d');
        $request = new PatientRequest($this->userRequest(Role::PATIENT), new PatientDataRequest($futureBirthDate));

        try {
            $this->service->create($request, true);
            $this->fail('Se esperaba una excepción');
        } catch (Exception $e) {
            // esperado: InvalidBirthDateException
        }

        $this->seeNumRecords(0, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
    }
}

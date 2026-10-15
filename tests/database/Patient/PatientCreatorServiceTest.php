<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Exception\Tenant\TenantFrontendUrlNotConfiguredException;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Tests\Support\Database\SeedsTenantEmailSettings;

/**
 * @internal
 */
final class PatientCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use SeedsTenantEmailSettings;

    protected $refresh = true;
    protected $namespace = null;

    private PatientCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenantEmailSettings();
        $this->service = new PatientCreatorService();
    }

    public function testPublicCreateWithoutFrontendUrlFailsWith500AndWritesNothing(): void
    {
        $this->db->table('tenant_settings')->where('tenant_id', 1)->update(['frontend_url' => null]);

        try {
            $this->service->create(new PatientRequest($this->userRequest(Role::PATIENT), $this->patientDataRequest()), false);
            $this->fail('Se esperaba TenantFrontendUrlNotConfiguredException');
        } catch (TenantFrontendUrlNotConfiguredException $e) {
            $this->assertSame(500, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }

    public function testStaffCreateDoesNotNeedFrontendUrl(): void
    {
        $this->db->table('tenant_settings')->where('tenant_id', 1)->update(['frontend_url' => null]);
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $response = $this->service->create(new PatientRequest($this->userRequest(Role::PATIENT), $this->patientDataRequest()), true);

        $this->assertNotNull($response->userId);
        $this->seeNumRecords(1, 'users', []);
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

    private function staffRequest(
        int $dni,
        ?string $email,
        ?string $password = null,
        ?string $phone = '2325506499',
        ?string $birthDate = null,
        string $firstName = 'Staff'
    ): PatientRequest {
        return new PatientRequest(
            new UserRequest($firstName . '_' . uniqid(), 'Apellido', $email, $phone, $password, $dni, true, Role::PATIENT),
            new PatientDataRequest($birthDate)
        );
    }

    /**
     * Siembra un usuario sin cuenta con su paciente, como los deja una reserva pública.
     *
     * @return array{0:int,1:int} [userId, patientId]
     */
    private function seedAccountlessPatient(int $dni, string $email, ?string $phone = null, bool $withPatient = true): array
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'reserva_' . uniqid(),
            'last_name'  => 'Publica',
            'email'      => $email,
            'phone'      => $phone,
            'password'   => null,
            'dni'        => $dni,
            'is_active'  => 1,
            'is_verified' => 0,
            'role_id'    => Role::PATIENT,
        ]);
        $userId = (int) $this->db->insertID();
        $patientId = 0;

        if ($withPatient) {
            $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => null]);
            $patientId = (int) $this->db->insertID();
        }

        return [$userId, $patientId];
    }

    public function testStaffCanCreatePatientWithoutPasswordOrBirthDate(): void
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $response = $this->service->create($this->staffRequest(30123456, 'sin-clave@test.com'), true);

        $this->assertNull($response->birthDate);
        $this->seeInDatabase('users', [
            'email'       => 'sin-clave@test.com',
            'password'    => null,
            'is_verified' => 1,
            'phone'       => '2325506499',
        ]);
        $this->seeNumRecords(1, 'patients', []);
    }

    public function testStaffCanCreateSeveralPatientsWithoutEmailInTheSameTenant(): void
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);

        $this->service->create($this->staffRequest(30123456, null), true);
        $this->service->create($this->staffRequest(30123457, null, null, '2325506400'), true);

        $this->seeNumRecords(2, 'users', ['email' => null]);
        $this->seeNumRecords(2, 'patients', []);
    }

    public function testStaffCreateReusesAccountlessUserAndPatientInsteadOfDuplicating(): void
    {
        [$userId, $patientId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com');

        $response = $this->service->create(
            $this->staffRequest(30123456, 'completo@test.com', null, '2325506499', '1990-05-20', 'Completo'),
            true
        );

        $this->assertSame($patientId, $response->id);
        $this->assertSame($userId, $response->userId);
        $this->assertSame('1990-05-20', $response->birthDate);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('users', [
            'id'          => $userId,
            'email'       => 'completo@test.com',
            'phone'       => '2325506499',
            'is_verified' => 1,
            'password'    => null,
        ]);
    }

    public function testStaffCreateCreatesMissingPatientForAnAccountlessUser(): void
    {
        [$userId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com', null, false);

        $response = $this->service->create($this->staffRequest(30123456, 'reserva@test.com'), true);

        $this->assertSame($userId, $response->userId);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
    }

    public function testStaffCreateKeepsExistingPhoneWhenRequestHasNone(): void
    {
        [$userId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com', '1144445555');

        $this->service->create($this->staffRequest(30123456, 'reserva@test.com', null, null), true);

        $this->seeInDatabase('users', ['id' => $userId, 'phone' => '1144445555']);
    }

    public function testStaffCreateWithAPasswordGivesTheReusedUserAnAccount(): void
    {
        [$userId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com');

        $this->service->create($this->staffRequest(30123456, 'reserva@test.com', 'contrasenia123'), true);

        $user = (new \App\Models\User\UserModel())->find($userId);
        $this->assertTrue($user->hasAccount());
        $this->assertTrue($user->verifyPassword('contrasenia123'));
    }

    public function testStaffCreateWithDniOfAnAccountHolderStillReturns409AndChangesNothing(): void
    {
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'titular_' . uniqid(),
            'last_name'  => 'ConCuenta',
            'email'      => 'titular@test.com',
            'phone'      => '1144445555',
            'password'   => password_hash('contrasenia123', PASSWORD_BCRYPT),
            'dni'        => 30123456,
            'role_id'    => Role::PATIENT,
        ]);

        try {
            $this->service->create($this->staffRequest(30123456, 'otro@test.com', null, '2325506499'), true);
            $this->fail('Se esperaba DniAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(1, 'users', []);
        $this->seeInDatabase('users', ['email' => 'titular@test.com', 'phone' => '1144445555']);
    }

    public function testStaffCreateRejectsAnEmailThatBelongsToAnotherUserAndRollsBack(): void
    {
        [$accountlessId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com', '1144445555');
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'otro_' . uniqid(),
            'last_name'  => 'Usuario',
            'email'      => 'ocupado@test.com',
            'password'   => 'hash',
            'dni'        => 30999999,
            'role_id'    => Role::PATIENT,
        ]);

        try {
            $this->service->create($this->staffRequest(30123456, 'ocupado@test.com'), true);
            $this->fail('Se esperaba EmailAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeInDatabase('users', [
            'id'          => $accountlessId,
            'email'       => 'reserva@test.com',
            'phone'       => '1144445555',
            'is_verified' => 0,
        ]);
    }

    public function testStaffCreateRollsBackTheCompletionWhenBirthDateIsInTheFuture(): void
    {
        [$userId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com');
        $future = (new DateTime('+1 day'))->format('Y-m-d');

        try {
            $this->service->create(
                $this->staffRequest(30123456, 'completo@test.com', null, '2325506499', $future),
                true
            );
            $this->fail('Se esperaba InvalidBirthDateException');
        } catch (Exception $e) {
            // esperado
        }

        $this->seeInDatabase('users', ['id' => $userId, 'email' => 'reserva@test.com', 'is_verified' => 0]);
    }

    public function testPublicCreateNeverReusesAnAccountlessUser(): void
    {
        [$userId] = $this->seedAccountlessPatient(30123456, 'reserva@test.com');

        try {
            $this->service->create($this->staffRequest(30123456, 'reserva@test.com', 'contrasenia123'), false);
            $this->fail('Se esperaba DniAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeInDatabase('users', ['id' => $userId, 'password' => null]);
        $this->seeNumRecords(1, 'users', []);
    }
}

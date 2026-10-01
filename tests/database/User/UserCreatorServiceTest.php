<?php

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Services\User\UserCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserCreatorServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private UserCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserCreatorService();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    private function userRequest(
        string $firstName,
        string $email,
        int $dni
    ): UserRequest {
        return new UserRequest($firstName, 'Apellido', $email, null, 'password123', $dni, true, Role::PATIENT);
    }

    public function testDuplicateEmailThrows409AndCreatesNoExtraUser(): void
    {
        $this->service->create($this->userRequest('usuario_uno', 'mismo@test.com', 11111111));

        try {
            $this->service->create($this->userRequest('usuario_dos', 'mismo@test.com', 22222222));
            $this->fail('Se esperaba EmailAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(1, 'users', []);
    }

    public function testDuplicateDniThrows409AndCreatesNoExtraUser(): void
    {
        $this->service->create($this->userRequest('usuario_uno', 'uno@test.com', 33333333));

        try {
            $this->service->create($this->userRequest('usuario_dos', 'dos@test.com', 33333333));
            $this->fail('Se esperaba DniAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(1, 'users', []);
    }

    public function testValidUserIsCreatedWithHydratedTimestamps(): void
    {
        $response = $this->service->create($this->userRequest('usuario_valido', 'valido@test.com', 44444444));

        $this->assertNotNull($response->id);
        $this->assertNotNull($response->createdAt);
        $this->seeNumRecords(1, 'users', []);
    }
}

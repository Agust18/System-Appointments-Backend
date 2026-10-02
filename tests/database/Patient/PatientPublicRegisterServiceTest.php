<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Exception\Email\EmailCannotBeSendedException;
use App\Services\Patient\PatientPublicRegisterService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * El registro público nunca acepta una contraseña para un usuario sin cuenta que
 * ya existe: le envía un link a su email guardado (anti pre-hijacking).
 *
 * El envío real de emails queda deshabilitado vaciando EMAIL_HOST: EmailModel
 * devuelve SMTP_CONFIG_ERROR y EmailSenderService lanza EmailCannotBeSendedException
 * sin abrir ninguna conexión. Por eso la rama de activación se verifica por el
 * token generado (que se crea antes de enviar) y por esa excepción.
 *
 * @internal
 */
final class PatientPublicRegisterServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientPublicRegisterService $service;
    private mixed $previousEmailHost;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousEmailHost = $_ENV['EMAIL_HOST'] ?? null;
        $_ENV['EMAIL_HOST'] = '';

        $this->service = new PatientPublicRegisterService();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    protected function tearDown(): void
    {
        if ($this->previousEmailHost === null) {
            unset($_ENV['EMAIL_HOST']);
        } else {
            $_ENV['EMAIL_HOST'] = $this->previousEmailHost;
        }

        parent::tearDown();
    }

    private function seedAccountless(
        string $email = 'reserva@test.com',
        int $roleId = Role::PATIENT,
        bool $active = true
    ): int {
        $this->db->table('users')->insert([
            'first_name'  => 'reserva_' . uniqid(),
            'last_name'   => 'Publica',
            'email'       => $email,
            'phone'       => '2325506499',
            'password'    => null,
            'dni'         => 30123456,
            'is_active'   => $active ? 1 : 0,
            'is_verified' => 0,
            'role_id'     => $roleId,
        ]);

        return (int) $this->db->insertID();
    }

    private function registerRequest(string $email, string $password = 'contrasenia-del-atacante'): PatientRequest
    {
        return new PatientRequest(
            new UserRequest('Nuevo_' . uniqid(), 'Apellido', $email, null, $password, 30123456, true, Role::PATIENT),
            new PatientDataRequest(null)
        );
    }

    public function testAccountlessUserWithSameEmailGetsAnActivationLinkAndNoPasswordIsSet(): void
    {
        $userId = $this->seedAccountless('reserva@test.com');

        try {
            $this->service->register($this->registerRequest('reserva@test.com'));
            $this->fail('Se esperaba EmailCannotBeSendedException (el envío está deshabilitado en tests)');
        } catch (EmailCannotBeSendedException) {
            // La rama de activación llegó hasta el envío del email.
        }

        $this->seeInDatabase('user_verification_tokens', ['user_id' => $userId, 'type' => 'password_reset']);
        $this->seeInDatabase('users', ['id' => $userId, 'password' => null, 'is_verified' => 0]);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
    }

    public function testAccountlessUserWithDifferentEmailIsRejectedAndGetsNoLink(): void
    {
        $userId = $this->seedAccountless('reserva@test.com');

        try {
            $this->service->register($this->registerRequest('otro@test.com'));
            $this->fail('Se esperaba DniAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeInDatabase('users', ['id' => $userId, 'password' => null]);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }

    public function testNonPatientUserWithSameDniAndEmailIsNotActivated(): void
    {
        $this->db->table('roles')->insert(['id' => Role::MEDICAL, 'name' => 'medical']);
        $userId = $this->seedAccountless('medico@test.com', Role::MEDICAL);

        try {
            $this->service->register($this->registerRequest('medico@test.com'));
            $this->fail('Se esperaba una excepción 409');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(0, 'user_verification_tokens', []);
        $this->seeInDatabase('users', ['id' => $userId, 'password' => null]);
    }

    public function testInactiveAccountlessUserIsNotActivated(): void
    {
        $userId = $this->seedAccountless('reserva@test.com', Role::PATIENT, false);

        try {
            $this->service->register($this->registerRequest('reserva@test.com'));
            $this->fail('Se esperaba una excepción 409');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(0, 'user_verification_tokens', []);
        $this->seeInDatabase('users', ['id' => $userId, 'password' => null]);
    }

    public function testAccountHolderIsNotTouchedAndGets409(): void
    {
        $this->db->table('users')->insert([
            'first_name' => 'titular_' . uniqid(),
            'last_name'  => 'ConCuenta',
            'email'      => 'titular@test.com',
            'password'   => password_hash('contrasenia123', PASSWORD_BCRYPT),
            'dni'        => 30123456,
            'role_id'    => Role::PATIENT,
        ]);

        try {
            $this->service->register($this->registerRequest('titular@test.com'));
            $this->fail('Se esperaba una excepción 409');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }
}

<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Dto\Request\Auth\UserLoginRequest;
use App\Exception\Auth\NotVerifiedException;
use App\Exception\Email\EmailCannotBeSendedException;
use App\Exception\Tenant\TenantFrontendUrlNotConfiguredException;
use App\Services\Auth\UserLoginService;
use App\Services\Patient\PatientPublicRegisterService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Tests\Support\Database\SeedsTenantEmailSettings;

/**
 * El registro público usa la contraseña del request tanto para un usuario nuevo como
 * para uno sin cuenta existente (mismo DNI y email): queda sin verificar y se le envía
 * el email de verificación.
 *
 * El envío real de emails queda deshabilitado vaciando EMAIL_HOST: EmailModel
 * devuelve SMTP_CONFIG_ERROR y EmailSenderService lanza EmailCannotBeSendedException
 * sin abrir ninguna conexión. Por eso se verifica el estado de la base y el token
 * generado (que se crea antes de enviar) y esa excepción.
 *
 * @internal
 */
final class PatientPublicRegisterServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use SeedsTenantEmailSettings;

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
        $this->seedTenantEmailSettings();
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
        $this->db->table('users')->insert(['tenant_id' => 1,
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

    private function registerExpectingEmailFailure(PatientRequest $request): void
    {
        try {
            $this->service->register($request);
            $this->fail('Se esperaba EmailCannotBeSendedException (el envío está deshabilitado en tests)');
        } catch (EmailCannotBeSendedException) {
            // Llegó hasta el envío del email de verificación.
        }
    }

    public function testWithoutFrontendUrlNewUserIsNotCreated(): void
    {
        $this->db->table('tenant_settings')->where('tenant_id', 1)->update(['frontend_url' => null]);

        try {
            $this->service->register($this->registerRequest('nuevo@test.com'));
            $this->fail('Se esperaba TenantFrontendUrlNotConfiguredException');
        } catch (TenantFrontendUrlNotConfiguredException $e) {
            $this->assertSame(500, $e->getCode());
        }

        $this->seeNumRecords(0, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }

    public function testWithoutFrontendUrlAccountlessUserKeepsNoPasswordAndGetsNoToken(): void
    {
        $this->db->table('tenant_settings')->where('tenant_id', 1)->update(['frontend_url' => null]);
        $userId = $this->seedAccountless('reserva@test.com');

        try {
            $this->service->register($this->registerRequest('reserva@test.com'));
            $this->fail('Se esperaba TenantFrontendUrlNotConfiguredException');
        } catch (TenantFrontendUrlNotConfiguredException) {
            // esperado
        }

        $this->seeInDatabase('users', ['id' => $userId, 'password' => null]);
        $this->seeNumRecords(0, 'patients', []);
        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }

    public function testAccountlessUserWithSameEmailGetsThePasswordAndAVerificationEmail(): void
    {
        $userId = $this->seedAccountless('reserva@test.com');

        $this->registerExpectingEmailFailure($this->registerRequest('reserva@test.com', 'mi-contrasenia-nueva'));

        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        $this->assertTrue(password_verify('mi-contrasenia-nueva', $user->password));
        $this->assertSame('0', (string) $user->is_verified);
        $this->assertSame('Publica', $user->last_name);
        $this->assertSame('2325506499', $user->phone);
        $this->assertStringStartsWith('reserva_', $user->first_name);
        $this->seeInDatabase('user_verification_tokens', ['user_id' => $userId, 'type' => 'email_verification']);
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', ['user_id' => $userId]);
    }

    public function testAccountlessUserWithExistingPatientDoesNotDuplicatePatients(): void
    {
        $userId = $this->seedAccountless('reserva@test.com');
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId]);

        $this->registerExpectingEmailFailure($this->registerRequest('reserva@test.com'));

        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('user_verification_tokens', ['user_id' => $userId, 'type' => 'email_verification']);
    }

    public function testNewUserIsCreatedWithPasswordUnverifiedAndAVerificationToken(): void
    {
        $this->registerExpectingEmailFailure($this->registerRequest('nuevo@test.com', 'contrasenia-nueva'));

        $user = $this->db->table('users')->where('email', 'nuevo@test.com')->get()->getRow();
        $this->assertTrue(password_verify('contrasenia-nueva', $user->password));
        $this->assertSame('0', (string) $user->is_verified);
        $this->seeInDatabase('user_verification_tokens', ['user_id' => $user->id, 'type' => 'email_verification']);
        $this->seeNumRecords(1, 'patients', ['user_id' => $user->id]);
    }

    public function testRegisteredUserCannotLoginUntilVerified(): void
    {
        $this->seedAccountless('reserva@test.com');
        $this->registerExpectingEmailFailure($this->registerRequest('reserva@test.com', 'mi-contrasenia-nueva'));

        $this->expectException(NotVerifiedException::class);

        (new UserLoginService())->login(new UserLoginRequest('reserva@test.com', 'mi-contrasenia-nueva'));
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
        $this->db->table('users')->insert(['tenant_id' => 1,
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

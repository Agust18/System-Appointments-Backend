<?php

use App\Constants\Role;
use App\Dto\Request\Auth\ResetPasswordRequest;
use App\Dto\Request\Auth\UserLoginRequest;
use App\Entity\User\User;
use App\Exception\Auth\WrongPasswordException;
use App\Models\User\UserModel;
use App\Services\auth\PasswordResetTokenService;
use App\Services\auth\ResetPasswordService;
use App\Services\auth\UserLoginService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Usuarios sin cuenta (users.password NULL): no pueden iniciar sesión y la
 * activan fijando una contraseña con el token enviado a su email.
 *
 * @internal
 */
final class AccountlessUserTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    private function insertAccountless(string $email = 'sin-cuenta@test.com', bool $verified = false): User
    {
        $user = User::createWithoutAccount(
            // Nombre único: ver el down() de ReplaceUsernameWithFirstLastName.
            'usuario_' . uniqid(),
            'SinCuenta',
            $email,
            '2325506499',
            random_int(1000000, 9999999),
            Role::PATIENT
        );

        if ($verified) {
            $user->markAsVerified();
        }

        return $this->userModel->insert($user);
    }

    public function testAccountlessUserIsPersistedWithNullPassword(): void
    {
        $user = $this->insertAccountless();

        $this->assertNotNull($user->getId());
        $this->assertFalse($user->hasAccount());
        $this->seeInDatabase('users', ['id' => $user->getId(), 'password' => null]);
    }

    public function testAccountlessUserIsHydratedWithoutAccountAfterReload(): void
    {
        $user = $this->insertAccountless();

        $reloaded = $this->userModel->find($user->getId());

        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->hasAccount());
    }

    public function testAccountlessUserCannotLogInEvenWhenVerified(): void
    {
        $this->insertAccountless('verificado@test.com', true);

        foreach (['', 'cualquier-cosa', 'contrasenia123'] as $password) {
            try {
                (new UserLoginService())->login(new UserLoginRequest('verificado@test.com', $password));
                $this->fail('Se esperaba WrongPasswordException');
            } catch (WrongPasswordException $e) {
                $this->assertSame(401, $e->getCode());
            }
        }

        $this->seeInDatabase('users', ['email' => 'verificado@test.com', 'token' => null]);
    }

    public function testSettingPasswordWithResetTokenActivatesTheAccount(): void
    {
        $user = $this->insertAccountless('activar@test.com');
        $token = (new PasswordResetTokenService())->generateForUser($user->getId());

        (new ResetPasswordService())->reset(new ResetPasswordRequest($token->getToken(), 'contrasenia123'));

        $reloaded = $this->userModel->find($user->getId());
        $this->assertTrue($reloaded->hasAccount());
        $this->assertTrue($reloaded->getIsVerified());
        $this->assertTrue($reloaded->verifyPassword('contrasenia123'));

        $login = (new UserLoginService())->login(new UserLoginRequest('activar@test.com', 'contrasenia123'));
        $this->assertNotEmpty($login->token);
    }

    public function testActivationReusesTheSameUserWithoutCreatingAnother(): void
    {
        $user = $this->insertAccountless('mismo@test.com');
        $token = (new PasswordResetTokenService())->generateForUser($user->getId());

        (new ResetPasswordService())->reset(new ResetPasswordRequest($token->getToken(), 'contrasenia123'));

        $this->seeNumRecords(1, 'users', []);
        $this->seeInDatabase('users', ['id' => $user->getId(), 'dni' => $user->getDni()]);
    }

    public function testResetKeepsAnAlreadyVerifiedUserVerified(): void
    {
        $user = $this->insertAccountless('verificado2@test.com', true);
        $token = (new PasswordResetTokenService())->generateForUser($user->getId());

        (new ResetPasswordService())->reset(new ResetPasswordRequest($token->getToken(), 'contrasenia123'));

        $this->assertTrue($this->userModel->find($user->getId())->getIsVerified());
    }
}

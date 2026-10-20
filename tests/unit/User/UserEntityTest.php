<?php

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Entity\User\User;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UserEntityTest extends CIUnitTestCase
{
    private function userWithPassword(?string $plainPassword): User
    {
        return User::convertFromRequest(
            new UserRequest('Juana', 'Pérez', 'juana@example.com', null, $plainPassword, 30123456, true, Role::RECEPTION)
        );
    }

    public function testUserWithPasswordHasAccountAndCanVerifyIt(): void
    {
        $user = $this->userWithPassword('contrasenia123');

        $this->assertTrue($user->hasAccount());
        $this->assertTrue($user->verifyPassword('contrasenia123'));
        $this->assertFalse($user->verifyPassword('otra-contrasenia'));
    }

    public function testConvertFromRequestWithoutPasswordCreatesAccountlessUser(): void
    {
        $user = $this->userWithPassword(null);

        $this->assertFalse($user->hasAccount());
        $this->assertNull($user->getPassword());
    }

    public function testAccountlessUserNeverVerifiesAnyPassword(): void
    {
        $user = $this->userWithPassword(null);

        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword('contrasenia123'));
    }




}

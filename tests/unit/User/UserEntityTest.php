<?php

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Dto\Request\User\UserUpdaterRequest;
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
            new UserRequest('Juana', 'Pérez', 'juana@example.com', null, $plainPassword, 30123456, true, Role::PATIENT)
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

    public function testCreateWithoutAccountIsActiveUnverifiedAndPasswordless(): void
    {
        $user = User::createWithoutAccount('Juana', 'Pérez', 'juana@example.com', '2325506499', 30123456, Role::PATIENT);

        $this->assertNull($user->getId());
        $this->assertFalse($user->hasAccount());
        $this->assertTrue($user->getIsActive());
        $this->assertFalse($user->getIsVerified());
        $this->assertSame(Role::PATIENT, $user->getRoleId());
        $this->assertSame('2325506499', $user->getPhone());
        $this->assertSame(30123456, $user->getDni());
    }

    public function testCompleteAsAccountlessFillsDataAndVerifiesWithoutCreatingAnAccount(): void
    {
        $user = User::createWithoutAccount('Juana', 'Pérez', 'juana@example.com', null, 30123456, Role::PATIENT);

        $user->completeAsAccountless(new UserUpdaterRequest(
            firstName: 'Juana María',
            email: 'nueva@example.com',
            phone: '2325506499',
        ));

        $this->assertSame('Juana María', $user->getFirstName());
        $this->assertSame('Pérez', $user->getLastName());
        $this->assertSame('nueva@example.com', $user->getEmail());
        $this->assertSame('2325506499', $user->getPhone());
        $this->assertTrue($user->getIsVerified());
        $this->assertFalse($user->hasAccount());
    }

    public function testCompleteAsAccountlessKeepsExistingPhoneWhenNoneIsGiven(): void
    {
        $user = User::createWithoutAccount('Juana', 'Pérez', 'juana@example.com', '2325506499', 30123456, Role::PATIENT);

        $user->completeAsAccountless(new UserUpdaterRequest(firstName: 'Juana'));

        $this->assertSame('2325506499', $user->getPhone());
    }

    public function testCompleteAsAccountlessHashesAPasswordGivenByStaff(): void
    {
        $user = User::createWithoutAccount('Juana', 'Pérez', 'juana@example.com', null, 30123456, Role::PATIENT);

        $user->completeAsAccountless(new UserUpdaterRequest(password: 'contrasenia123'));

        $this->assertTrue($user->hasAccount());
        $this->assertTrue($user->verifyPassword('contrasenia123'));
        $this->assertNotSame('contrasenia123', $user->getPassword());
    }
}

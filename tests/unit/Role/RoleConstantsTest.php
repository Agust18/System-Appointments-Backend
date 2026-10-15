<?php

use App\Constants\Role;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Role::NAMES es la lista única de roles (la usa el RolesSeeder) e isValid() es la validación
 * del roleId al crear/editar usuarios.
 *
 * @internal
 */
final class RoleConstantsTest extends CIUnitTestCase
{
    public function testEveryRoleConstantIsValid(): void
    {
        foreach ([Role::ADMIN, Role::MEDICAL, Role::PATIENT, Role::RECEPTION] as $id) {
            $this->assertTrue(Role::isValid($id));
        }
    }

    public function testUnknownIdsAreRejected(): void
    {
        foreach ([0, -1, 5, 99] as $id) {
            $this->assertFalse(Role::isValid($id));
        }
    }

    public function testNamesCoverExactlyTheConstants(): void
    {
        $this->assertSame(
            [Role::ADMIN, Role::MEDICAL, Role::PATIENT, Role::RECEPTION],
            array_keys(Role::NAMES)
        );
    }
}

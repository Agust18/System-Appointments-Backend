<?php

use App\Constants\Role;
use App\Filters\RoleFilter;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class RoleFilterTest extends CIUnitTestCase
{
    private RoleFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new RoleFilter();
        session()->destroy();
    }

    private function filterFor(?array $arguments): mixed
    {
        return $this->filter->before(Services::request(null, false), $arguments);
    }

    private function assertForbidden(mixed $result): void
    {
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testAllowedRolePasses(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->assertNotInstanceOf(ResponseInterface::class, $this->filterFor(['admin']));
    }

    public function testEveryRoleOfTheListPasses(): void
    {
        foreach ([Role::ADMIN, Role::RECEPTION] as $roleId) {
            session()->set('roleId', $roleId);

            $this->assertNotInstanceOf(ResponseInterface::class, $this->filterFor(['admin', 'reception']));
        }
    }

    public function testRoleOutsideTheListIsForbidden(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $this->assertForbidden($this->filterFor(['admin', 'reception']));
    }

    public function testRetiredPatientRoleIdIsForbidden(): void
    {
        session()->set('roleId', 3);

        $this->assertForbidden($this->filterFor(['admin']));
    }

    public function testNoRoleInSessionIsForbidden(): void
    {
        $this->assertForbidden($this->filterFor(['admin']));
    }

    public function testUnknownRoleNameNeverMatches(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->assertForbidden($this->filterFor(['superuser']));
    }

    public function testRouteWithoutArgumentsIsForbidden(): void
    {
        session()->set('roleId', Role::ADMIN);

        $this->assertForbidden($this->filterFor(null));
    }
}

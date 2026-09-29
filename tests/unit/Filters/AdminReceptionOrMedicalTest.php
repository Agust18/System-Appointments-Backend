<?php

use App\Constants\Role;
use App\Filters\AdminReceptionOrMedical;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class AdminReceptionOrMedicalTest extends CIUnitTestCase
{
    private AdminReceptionOrMedical $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new AdminReceptionOrMedical();
        session()->destroy();
    }

    private function request(): \CodeIgniter\HTTP\RequestInterface
    {
        return Services::request(null, false);
    }

    public function testAdminPasses(): void
    {
        session()->set('roleId', Role::ADMIN);

        $result = $this->filter->before($this->request());

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testReceptionPasses(): void
    {
        session()->set('roleId', Role::RECEPTION);

        $result = $this->filter->before($this->request());

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalPasses(): void
    {
        session()->set('roleId', Role::MEDICAL);

        $result = $this->filter->before($this->request());

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testPatientForbidden(): void
    {
        session()->set('roleId', Role::PATIENT);

        $result = $this->filter->before($this->request());

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testNoRoleForbidden(): void
    {
        $result = $this->filter->before($this->request());

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }
}

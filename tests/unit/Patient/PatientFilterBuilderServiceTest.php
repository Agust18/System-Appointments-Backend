<?php

use App\Constants\Role;
use App\Dto\Request\PaginationRequest;
use App\Services\Patient\PatientFilterBuilderService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PatientFilterBuilderServiceTest extends CIUnitTestCase
{
    private PatientFilterBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientFilterBuilderService();
        session()->destroy();
    }

    private function pagination(): PaginationRequest
    {
        return new PaginationRequest(1, 10);
    }

    public function testMedicalGetsOwnUserIdAsMedicalUserId(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 42);

        $request = $this->service->build($this->pagination(), null);

        $this->assertTrue($request->hasMedicalUserId());
        $this->assertSame(42, $request->getMedicalUserId());
    }

    public function testAdminGetsNoMedicalUserIdRestriction(): void
    {
        session()->set('roleId', Role::ADMIN);
        session()->set('userId', 1);

        $request = $this->service->build($this->pagination(), null);

        $this->assertFalse($request->hasMedicalUserId());
        $this->assertNull($request->getMedicalUserId());
    }

    public function testReceptionGetsNoMedicalUserIdRestriction(): void
    {
        session()->set('roleId', Role::RECEPTION);
        session()->set('userId', 7);

        $request = $this->service->build($this->pagination(), null);

        $this->assertFalse($request->hasMedicalUserId());
    }

    public function testSearchIsPassedThrough(): void
    {
        session()->set('roleId', Role::ADMIN);
        session()->set('userId', 1);

        $request = $this->service->build($this->pagination(), 'juan');

        $this->assertSame('juan', $request->getSearch());
    }
}

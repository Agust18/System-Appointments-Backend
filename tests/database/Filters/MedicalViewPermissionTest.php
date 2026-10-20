<?php

use App\Constants\Role;
use App\Filters\MedicalViewPermission;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class MedicalViewPermissionTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private MedicalViewPermission $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new MedicalViewPermission();
        session()->destroy();
    }

    private function seedUser(): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'user_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);

        return $this->db->insertID();
    }

    private function seedMedical(int $userId): int
    {
        $this->db->table('specialties')->insert(['tenant_id' => 1, 'name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert(['tenant_id' => 1,
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function requestFor(string $path): \CodeIgniter\HTTP\RequestInterface
    {
        $request = Services::request(null, false);
        $request->getUri()->setPath($path);

        return $request;
    }

    public function testAdminCanViewAnyMedical(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        session()->set('roleId', Role::ADMIN);
        session()->set('userId', 999);

        $result = $this->filter->before($this->requestFor("medicals/{$medicalId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalCanViewOwnProfileById(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("medicals/{$medicalId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalCanViewOwnProfileByUserId(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("medicals/user/{$medicalUserId}"));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalCannotViewOtherMedicalById(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $otherMedicalUserId = $this->seedUser();
        $this->seedMedical($otherMedicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $otherMedicalUserId);

        $result = $this->filter->before($this->requestFor("medicals/{$medicalId}"));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testMedicalCannotViewOtherMedicalByUserId(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        $otherMedicalUserId = $this->seedUser();
        $this->seedMedical($otherMedicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $otherMedicalUserId);

        $result = $this->filter->before($this->requestFor("medicals/user/{$medicalUserId}"));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }



    public function testUnknownMedicalByIdReturns404(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 1);

        $result = $this->filter->before($this->requestFor('medicals/999999'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }

    public function testUnknownMedicalByUserIdReturns404(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 1);

        $result = $this->filter->before($this->requestFor('medicals/user/999999'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }
}

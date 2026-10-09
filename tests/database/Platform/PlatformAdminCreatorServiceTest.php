<?php

use App\Dto\Request\Platform\PlatformAdminRequest;
use App\Exception\Platform\PlatformAdminEmailAlreadyExistsException;
use App\Models\Platform\PlatformAdminModel;
use App\Services\Platform\PlatformAdminCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class PlatformAdminCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private function request(string $email = 'root@plataforma.test'): PlatformAdminRequest
    {
        return new PlatformAdminRequest($email, 'Root', 'Admin', 'Secreta-12345');
    }

    public function testCreatesAnActiveAdminWithAHashedPassword(): void
    {
        $response = (new PlatformAdminCreatorService())->create($this->request());

        $this->assertSame('root@plataforma.test', $response->email);
        $this->assertTrue($response->isActive);

        $admin = (new PlatformAdminModel())->find($response->id);
        $this->assertNotSame('Secreta-12345', $admin->getPassword());
        $this->assertTrue($admin->verifyPassword('Secreta-12345'));
    }

    public function testResponseNeverCarriesThePassword(): void
    {
        $json = json_encode((new PlatformAdminCreatorService())->create($this->request()));

        $this->assertStringNotContainsString('assword', $json);
    }

    public function testDuplicateEmailIs409(): void
    {
        $service = new PlatformAdminCreatorService();
        $service->create($this->request());

        try {
            $service->create($this->request());
            $this->fail('Debió rechazar el email repetido.');
        } catch (PlatformAdminEmailAlreadyExistsException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }

    public function testRulesRequireAtLeast12CharactersForThePassword(): void
    {
        $passes = static function (string $password): bool {
            $validation = \Config\Services::validation(null, false);
            $validation->setRules(PlatformAdminRequest::rules());

            return $validation->run(['email' => 'root@plataforma.test', 'firstName' => 'Root', 'lastName' => 'Admin', 'password' => $password]);
        };

        $this->assertFalse($passes('12345678901'));
        $this->assertTrue($passes('123456789012'));
    }
}

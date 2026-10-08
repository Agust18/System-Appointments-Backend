<?php

use App\Dto\Request\Platform\PlatformLoginRequest;
use App\Entity\Platform\PlatformAdmin;
use App\Exception\Platform\PlatformAdminInactiveException;
use App\Exception\Platform\PlatformInvalidCredentialsException;
use App\Models\Platform\PlatformAdminModel;
use App\Services\Platform\PlatformLoginService;
use App\Services\Platform\PlatformTokenValidatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class PlatformLoginServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PlatformAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = (new PlatformAdminModel())->insert(
            PlatformAdmin::create('root@plataforma.test', 'Root', 'Admin', 'Secreta-123')
        );
    }

    private function login(string $email, string $password)
    {
        return (new PlatformLoginService())->login(new PlatformLoginRequest($email, $password));
    }

    public function testLoginReturnsATokenThatValidates(): void
    {
        $response = $this->login('root@plataforma.test', 'Secreta-123');

        $this->assertSame(64, strlen($response->token));
        $this->assertSame($this->admin->getId(), $response->admin->id);
        $this->assertSame($this->admin->getId(), (new PlatformTokenValidatorService())->validate($response->token)->getId());
    }

    public function testLoginResponseDoesNotExposeThePasswordHash(): void
    {
        $json = json_encode($this->login('root@plataforma.test', 'Secreta-123'));

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('$2y$', $json);
    }

    public function testWrongPasswordAndUnknownEmailGiveTheSameError(): void
    {
        $messages = [];
        foreach ([['root@plataforma.test', 'incorrecta'], ['nadie@plataforma.test', 'Secreta-123']] as [$email, $password]) {
            try {
                $this->login($email, $password);
                $this->fail('Debió fallar el login.');
            } catch (PlatformInvalidCredentialsException $e) {
                $messages[] = [$e->getCode(), $e->getMessage()];
            }
        }

        $this->assertSame(401, $messages[0][0]);
        $this->assertSame($messages[0], $messages[1]);
    }

    public function testInactiveAdminWithTheRightPasswordGets403(): void
    {
        $this->db->query("UPDATE platform_admins SET is_active = 0");

        $this->expectException(PlatformAdminInactiveException::class);
        $this->login('root@plataforma.test', 'Secreta-123');
    }

    public function testInactiveAdminWithAWrongPasswordGets401NotThe403(): void
    {
        $this->db->query("UPDATE platform_admins SET is_active = 0");

        $this->expectException(PlatformInvalidCredentialsException::class);
        $this->login('root@plataforma.test', 'incorrecta');
    }

    public function testInactiveAdminCannotLoginButNoTokenIsIssued(): void
    {
        $this->db->query("UPDATE platform_admins SET is_active = 0");

        try {
            $this->login('root@plataforma.test', 'Secreta-123');
        } catch (PlatformAdminInactiveException) {
        }

        $count = $this->db->query("SELECT COUNT(*) AS c FROM platform_admin_tokens")->getRow()->c;
        $this->assertSame(0, (int) $count);
    }

    public function testLoginCleansExpiredTokensOfThatAdmin(): void
    {
        $this->db->query(
            "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?, 'viejo', '2020-01-01 00:00:00')",
            [$this->admin->getId()]
        );

        $this->login('root@plataforma.test', 'Secreta-123');

        $tokens = array_column($this->db->query("SELECT token FROM platform_admin_tokens")->getResultArray(), 'token');
        $this->assertNotContains('viejo', $tokens);
        $this->assertCount(1, $tokens);
    }

    public function testValidatorRejectsEmptyAndUnknownTokens(): void
    {
        $validator = new PlatformTokenValidatorService();

        $this->assertNull($validator->validate(''));
        $this->assertNull($validator->validate('   '));
        $this->assertNull($validator->validate('inventado'));
    }
}

<?php

use App\Entity\Platform\PlatformAdmin;
use App\Entity\Platform\PlatformAdminToken;
use App\Filters\PlatformAdminOnly;
use App\Models\Platform\PlatformAdminModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class PlatformAdminOnlyFilterTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PlatformAdminOnly $filter;
    private PlatformAdminModel $adminModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetServices();
        $this->filter = new PlatformAdminOnly();
        $this->adminModel = new PlatformAdminModel();
        $this->clearSession();
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $this->clearSession();
        $this->resetServices();
        parent::tearDown();
    }

    private function clearSession(): void
    {
        foreach (['platformAdminId', 'userId', 'roleId'] as $key) {
            session()->remove($key);
        }
    }

    private function callFilter(?string $token)
    {
        if ($token !== null) {
            $_SERVER['HTTP_AUTHORIZATION'] = $token;
        }

        return $this->filter->before(Services::request(null, false));
    }

    private function adminWithToken(bool $active = true): array
    {
        $admin = $this->adminModel->insert(PlatformAdmin::create('root@plataforma.test', 'Root', 'Admin', 'Secreta-123'));
        $token = PlatformAdminToken::issueFor($admin->getId());
        $this->adminModel->saveToken($token);

        if (!$active) {
            $this->db->query('UPDATE platform_admins SET is_active = 0 WHERE id = ?', [$admin->getId()]);
        }

        return [$admin, $token->getToken()];
    }

    public function testMissingTokenIs401(): void
    {
        $result = $this->callFilter(null);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(401, $result->getStatusCode());
    }

    public function testInventedTokenIs401(): void
    {
        $this->adminWithToken();

        $result = $this->callFilter('inventado');

        $this->assertSame(401, $result->getStatusCode());
        $this->assertNull(session()->get('platformAdminId'));
    }

    public function testExpiredTokenIs401(): void
    {
        [$admin] = $this->adminWithToken();
        $this->db->query(
            "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?, 'vencido', '2020-01-01 00:00:00')",
            [$admin->getId()]
        );

        $this->assertSame(401, $this->callFilter('vencido')->getStatusCode());
    }

    public function testATenantUserTokenIs401(): void
    {
        $this->adminWithToken();
        $this->db->table('roles')->insert(['id' => 1, 'name' => 'admin']);
        $this->db->query(
            "INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, is_active, is_verified, role_id, token, token_expiration_date)
             VALUES (1, 'Ana', 'Admin', 'ana@demo.test', 'x', 30123456, 1, 1, 1, 'token-de-usuario', '2099-01-01 00:00:00')"
        );

        $result = $this->callFilter('token-de-usuario');

        $this->assertSame(401, $result->getStatusCode());
        $this->assertNull(session()->get('platformAdminId'));
    }

    public function testInactiveAdminWithAValidTokenIs403(): void
    {
        [, $token] = $this->adminWithToken(false);

        $result = $this->callFilter($token);

        $this->assertSame(403, $result->getStatusCode());
        $this->assertNull(session()->get('platformAdminId'));
    }

    public function testValidTokenPassesAndStoresOnlyThePlatformAdminId(): void
    {
        [$admin, $token] = $this->adminWithToken();

        $result = $this->callFilter($token);

        $this->assertNull($result);
        $this->assertSame($admin->getId(), session()->get('platformAdminId'));
        $this->assertNull(session()->get('userId'));
        $this->assertNull(session()->get('roleId'));
    }

    public function testUnauthorizedAnswerDoesNotRevealWhyTheTokenFailed(): void
    {
        [, $realToken] = $this->adminWithToken();

        $invented = json_decode($this->callFilter('inventado')->getBody(), true)['message'];
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $this->db->query('DELETE FROM platform_admin_tokens WHERE token = ?', [$realToken]);
        $deleted = json_decode($this->callFilter($realToken)->getBody(), true)['message'];

        $this->assertSame($invented, $deleted);
    }
}

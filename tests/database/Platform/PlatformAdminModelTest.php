<?php

use App\Entity\Platform\PlatformAdmin;
use App\Entity\Platform\PlatformAdminToken;
use App\Models\Platform\PlatformAdminModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class PlatformAdminModelTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PlatformAdminModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PlatformAdminModel();
    }

    private function createAdmin(string $email = 'root@plataforma.test'): PlatformAdmin
    {
        return $this->model->insert(PlatformAdmin::create($email, 'Root', 'Admin', 'Secreta-123'));
    }

    private function insertRawToken(int $adminId, string $token, string $expiresAt): void
    {
        $this->db->query(
            "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?,?,?)",
            [$adminId, $token, $expiresAt]
        );
    }

    public function testInsertReturnsTheHydratedAdmin(): void
    {
        $admin = $this->createAdmin();

        $this->assertNotNull($admin->getId());
        $this->assertSame('root@plataforma.test', $admin->getEmail());
        $this->assertTrue($admin->isActive());
        $this->assertTrue($admin->verifyPassword('Secreta-123'));
    }

    public function testFindByEmailAndFindById(): void
    {
        $admin = $this->createAdmin();

        $this->assertSame($admin->getId(), $this->model->findByEmail('root@plataforma.test')->getId());
        $this->assertSame($admin->getId(), $this->model->find($admin->getId())->getId());
        $this->assertNull($this->model->findByEmail('otro@plataforma.test'));
    }

    public function testFindByTokenReturnsTheOwnerOfAValidToken(): void
    {
        $admin = $this->createAdmin();
        $token = PlatformAdminToken::issueFor($admin->getId());
        $this->model->saveToken($token);

        $found = $this->model->findByToken($token->getToken());

        $this->assertSame($admin->getId(), $found->getId());
    }

    public function testFindByTokenIgnoresExpiredAndUnknownTokens(): void
    {
        $admin = $this->createAdmin();
        $this->insertRawToken($admin->getId(), 'vencido', '2020-01-01 00:00:00');

        $this->assertNull($this->model->findByToken('vencido'));
        $this->assertNull($this->model->findByToken('no-existe'));
    }

    public function testFindByTokenStillReturnsAnInactiveAdminSoTheFilterCanAnswer403(): void
    {
        $admin = $this->createAdmin();
        $token = PlatformAdminToken::issueFor($admin->getId());
        $this->model->saveToken($token);
        $this->db->query("UPDATE platform_admins SET is_active = 0 WHERE id = ?", [$admin->getId()]);

        $found = $this->model->findByToken($token->getToken());

        $this->assertNotNull($found);
        $this->assertFalse($found->isActive());
    }

    public function testATenantUserTokenIsNotAPlatformToken(): void
    {
        $this->createAdmin();
        $this->db->table('roles')->insert(['id' => 1, 'name' => 'admin']);
        $this->db->query(
            "INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, is_active, is_verified, role_id, token, token_expiration_date)
             VALUES (1, 'Ana', 'Admin', 'ana@demo.test', 'x', 30123456, 1, 1, 1, 'token-de-usuario', '2099-01-01 00:00:00')"
        );

        $this->assertNull($this->model->findByToken('token-de-usuario'));
    }

    public function testDeleteExpiredTokensOnlyRemovesTheExpiredOnesOfThatAdmin(): void
    {
        $a = $this->createAdmin('a@plataforma.test');
        $b = $this->createAdmin('b@plataforma.test');
        $this->insertRawToken($a->getId(), 'a-vencido', '2020-01-01 00:00:00');
        $this->insertRawToken($a->getId(), 'a-vigente', '2099-01-01 00:00:00');
        $this->insertRawToken($b->getId(), 'b-vencido', '2020-01-01 00:00:00');

        $this->model->deleteExpiredTokens($a->getId());

        $left = array_column(
            $this->db->query("SELECT token FROM platform_admin_tokens ORDER BY token")->getResultArray(),
            'token'
        );
        $this->assertSame(['a-vigente', 'b-vencido'], $left);
    }
}

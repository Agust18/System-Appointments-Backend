<?php

use App\Entity\Tenant\Tenant;
use App\Entity\UserToken\UserToken;
use App\Exception\Tenant\TenantNotResolvedException;
use App\Models\UserToken\UserTokenModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Un Tenant nunca ve, marca ni borra los tokens de verificación/reset de otro, y el Model nunca
 * escribe con el default: usa el contexto o lanza.
 *
 * @internal
 */
final class UserTokenTenantIsolationTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantA = 1;
    private int $tenantB;

    /** @var array<string, int> */
    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        $this->a = $this->seedWorld($this->tenantA, 'a');
        $this->b = $this->seedWorld($this->tenantB, 'b');
        $this->actAs($this->tenantA);
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        $this->db->query('DELETE FROM user_verification_tokens');
        $this->db->query('DELETE FROM users');
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    /** Un usuario con un token vigente y otro vencido, en el Tenant indicado. */
    private function seedWorld(int $tenantId, string $tag): array
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'U', 'last_name' => $tag,
            'email' => "u-{$tag}@test.com", 'password' => 'hash', 'dni' => 32000000 + $tenantId,
        ]);
        $userId = $this->db->insertID();

        $this->db->table('user_verification_tokens')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'token' => "vigente-{$tag}",
            'type' => 'email_verification', 'expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
        $activeId = $this->db->insertID();

        $this->db->table('user_verification_tokens')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'token' => "vencido-{$tag}",
            'type' => 'password_reset', 'expires_at' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        return ['user' => $userId, 'active' => $activeId];
    }

    public function testFindByTokenOnlySeesTheTenantsOwnTokens(): void
    {
        $model = new UserTokenModel();

        $this->assertNotNull($model->findByToken('vigente-a'));
        $this->assertNull($model->findByToken('vigente-b'));
    }

    public function testFindActiveByUserAndTypeOnlySeesTheTenantsOwnTokens(): void
    {
        $model = new UserTokenModel();

        $this->assertNotNull($model->findActiveByUserAndType($this->a['user'], 'email_verification'));
        $this->assertNull($model->findActiveByUserAndType($this->b['user'], 'email_verification'));
    }

    public function testMarkAsUsedCannotTouchAnotherTenantsToken(): void
    {
        (new UserTokenModel())->markAsUsed($this->b['active']);

        $row = $this->db->table('user_verification_tokens')->where('id', $this->b['active'])->get()->getRow();
        $this->assertNull($row->used_at);
    }

    public function testDeleteByUserAndTypeCannotTouchAnotherTenantsTokens(): void
    {
        (new UserTokenModel())->deleteByUserAndType($this->b['user'], 'email_verification');

        $this->assertSame(1, (int) $this->db->table('user_verification_tokens')->where('token', 'vigente-b')->countAllResults());
    }

    public function testDeleteExpiredOnlyDeletesTheContextTenantsExpiredTokens(): void
    {
        $deleted = (new UserTokenModel())->deleteExpired();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, (int) $this->db->table('user_verification_tokens')->where('token', 'vencido-a')->countAllResults());
        $this->assertSame(1, (int) $this->db->table('user_verification_tokens')->where('token', 'vencido-b')->countAllResults(), 'el vencido del otro Tenant no se toca');
        $this->assertSame(1, (int) $this->db->table('user_verification_tokens')->where('token', 'vigente-a')->countAllResults(), 'los vigentes no se tocan');
    }

    public function testInsertWritesTheContextTenantNotTheDefault(): void
    {
        $this->actAs($this->tenantB);

        (new UserTokenModel())->insert(new UserToken(
            null, $this->b['user'], 'nuevo-b', 'email_verification', new DateTime('+1 hour'), null, new DateTime()
        ));

        $row = $this->db->table('user_verification_tokens')->where('token', 'nuevo-b')->get()->getRow();
        $this->assertSame($this->tenantB, (int) $row->tenant_id);
        $this->assertNotSame(1, (int) $row->tenant_id, 'no debe caer al DEFAULT 1');
    }

    public function testInsertWithoutAResolvedTenantThrowsInsteadOfUsingTheDefault(): void
    {
        TenantContext::clear();

        try {
            (new UserTokenModel())->insert(new UserToken(
                null, $this->a['user'], 'sin-contexto', 'email_verification', new DateTime('+1 hour'), null, new DateTime()
            ));
            $this->fail('Se esperaba TenantNotResolvedException');
        } catch (TenantNotResolvedException) {
            $this->assertSame(0, (int) $this->db->table('user_verification_tokens')->where('token', 'sin-contexto')->countAllResults());
        }
    }
}

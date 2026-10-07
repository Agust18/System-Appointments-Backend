<?php

use App\Constants\TenantStatus;
use App\Models\Tenant\TenantModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class TenantModelTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    protected function tearDown(): void
    {
        $this->db->query('DELETE FROM tenants WHERE id <> 1');
        parent::tearDown();
    }

    private function seedTenant(string $slug, string $status): int
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => 'Tenant ' . $slug, 'status' => $status]);

        return $this->db->insertID();
    }

    public function testFindAllActiveReturnsOnlyActiveTenantsOrderedById(): void
    {
        $b = $this->seedTenant('tenant-b', TenantStatus::ACTIVE);
        $this->seedTenant('suspendido', TenantStatus::SUSPENDED);
        $d = $this->seedTenant('tenant-d', TenantStatus::ACTIVE);

        $ids = array_map(static fn ($tenant) => $tenant->getId(), (new TenantModel())->findAllActive());

        // El Tenant 1 ('demo') lo siembra la migración y está activo.
        $this->assertSame([1, $b, $d], $ids);
    }

    public function testFindAllActiveIsEmptyWhenEveryTenantIsSuspended(): void
    {
        $this->db->query("UPDATE tenants SET status = 'suspended'");

        $this->assertSame([], (new TenantModel())->findAllActive());
    }
}

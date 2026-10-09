<?php

use App\Constants\TenantStatus;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Platform\TenantFilterRequest;
use App\Dto\Request\Platform\TenantUpdateRequest;
use App\Exception\Tenant\TenantNotFoundException;
use App\Services\Platform\Tenant\TenantFinderService;
use App\Services\Platform\Tenant\TenantsSearcherService;
use App\Services\Platform\Tenant\TenantUpdaterService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class TenantUpdaterServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private function seedTenant(string $slug = 'clinica-sur'): int
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => 'Clínica Sur', 'status' => TenantStatus::ACTIVE]);

        return $this->db->insertID();
    }

    public function testSuspendAndReactivate(): void
    {
        $id = $this->seedTenant();
        $service = new TenantUpdaterService();

        $suspended = $service->update(new TenantUpdateRequest(null, TenantStatus::SUSPENDED), $id);
        $this->assertSame(TenantStatus::SUSPENDED, $suspended->status);

        $reactivated = $service->update(new TenantUpdateRequest(null, TenantStatus::ACTIVE), $id);
        $this->assertSame(TenantStatus::ACTIVE, $reactivated->status);
    }

    public function testSuspendingKeepsTheTenantData(): void
    {
        $id = $this->seedTenant();
        $this->db->table('specialties')->insert(['tenant_id' => $id, 'name' => 'Cardiología']);

        (new TenantUpdaterService())->update(new TenantUpdateRequest(null, TenantStatus::SUSPENDED), $id);

        $count = $this->db->query("SELECT COUNT(*) AS c FROM specialties WHERE tenant_id = ?", [$id])->getRow()->c;
        $this->assertSame(1, (int) $count);
    }

    public function testUpdateRenamesWithoutTouchingTheStatusOrSlug(): void
    {
        $id = $this->seedTenant();

        $response = (new TenantUpdaterService())->update(new TenantUpdateRequest('Clínica Sur SA', null), $id);

        $this->assertSame('Clínica Sur SA', $response->name);
        $this->assertSame(TenantStatus::ACTIVE, $response->status);
        $this->assertSame('clinica-sur', $response->slug);
    }

    public function testUpdatingAnUnknownTenantIs404(): void
    {
        $this->expectException(TenantNotFoundException::class);
        (new TenantUpdaterService())->update(new TenantUpdateRequest('X', null), 9999);
    }

    public function testFinderReturns404ForUnknownTenant(): void
    {
        $this->expectException(TenantNotFoundException::class);
        (new TenantFinderService())->findResponse(9999);
    }

    public function testSearcherReturnsResponsesAndTheTotal(): void
    {
        $this->seedTenant('clinica-sur');
        $this->seedTenant('clinica-norte');

        $result = (new TenantsSearcherService())->searchResponses(
            new TenantFilterRequest(new PaginationRequest(1, 2), null, null)
        );

        $this->assertCount(2, $result['data']);
        $this->assertSame(3, $result['total']);
        $this->assertSame('demo', $result['data'][0]->slug);
    }
}

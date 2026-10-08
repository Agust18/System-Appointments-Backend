<?php

use App\Constants\TenantStatus;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Platform\TenantFilterRequest;
use App\Dto\Request\Platform\TenantUpdateRequest;
use App\Models\Tenant\TenantModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Métodos de TenantModel que usa el panel de plataforma.
 *
 * @internal
 */
final class TenantModelPlatformTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private TenantModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TenantModel();
    }

    private function seedTenant(string $slug, string $name, string $status = TenantStatus::ACTIVE): int
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => $name, 'status' => $status]);

        return $this->db->insertID();
    }

    private function filter(?string $search = null, ?string $status = null, int $page = 1, int $size = 10): TenantFilterRequest
    {
        return new TenantFilterRequest(new PaginationRequest($page, $size), $search, $status);
    }

    /** @return int[] */
    private function ids(TenantFilterRequest $filter): array
    {
        return array_map(static fn ($t) => $t->getId(), $this->model->search($filter));
    }

    public function testExistsBySlug(): void
    {
        $this->assertTrue($this->model->existsBySlug('demo'));
        $this->assertFalse($this->model->existsBySlug('inexistente'));
    }

    public function testUpdateChangesNameAndStatusButNotTheSlug(): void
    {
        $id = $this->seedTenant('clinica-sur', 'Clínica Sur');
        $tenant = $this->model->find($id);
        $tenant->modify(new TenantUpdateRequest('Clínica Sur SA', TenantStatus::SUSPENDED));

        $updated = $this->model->update($tenant);

        $this->assertSame('clinica-sur', $updated->getSlug());
        $this->assertSame('Clínica Sur SA', $updated->getName());
        $this->assertTrue($updated->isSuspended());
    }

    public function testUpdateOnlyTouchesThatTenant(): void
    {
        $id = $this->seedTenant('clinica-sur', 'Clínica Sur');
        $tenant = $this->model->find($id);
        $tenant->modify(new TenantUpdateRequest(null, TenantStatus::SUSPENDED));

        $this->model->update($tenant);

        $this->assertFalse($this->model->find(1)->isSuspended());
    }

    public function testSearchWithoutFiltersListsEveryTenantOrderedById(): void
    {
        $b = $this->seedTenant('b', 'Beta');
        $c = $this->seedTenant('c', 'Gamma', TenantStatus::SUSPENDED);

        $this->assertSame([1, $b, $c], $this->ids($this->filter()));
        $this->assertSame(3, $this->model->countByFilter($this->filter()));
    }

    public function testSearchFiltersByStatus(): void
    {
        $this->seedTenant('b', 'Beta');
        $c = $this->seedTenant('c', 'Gamma', TenantStatus::SUSPENDED);

        $this->assertSame([$c], $this->ids($this->filter(null, TenantStatus::SUSPENDED)));
        $this->assertSame(1, $this->model->countByFilter($this->filter(null, TenantStatus::SUSPENDED)));
    }

    public function testSearchMatchesNameOrSlug(): void
    {
        $sur = $this->seedTenant('clinica-sur', 'Sanatorio Austral');
        $norte = $this->seedTenant('norte', 'Clínica del Norte');

        $this->assertSame([$sur, $norte], $this->ids($this->filter('clinica')));
        $this->assertSame([$sur], $this->ids($this->filter('austral')));
        $this->assertSame(2, $this->model->countByFilter($this->filter('clinica')));
    }

    public function testSearchPaginatesButCountIsTheTotal(): void
    {
        $b = $this->seedTenant('b', 'Beta');
        $c = $this->seedTenant('c', 'Gamma');

        $this->assertSame([1, $b], $this->ids($this->filter(null, null, 1, 2)));
        $this->assertSame([$c], $this->ids($this->filter(null, null, 2, 2)));
        $this->assertSame(3, $this->model->countByFilter($this->filter(null, null, 2, 2)));
    }

    public function testDeleteRemovesOnlyThatTenant(): void
    {
        $id = $this->seedTenant('temporal', 'Temporal');

        $this->model->delete($id);

        $this->assertNull($this->model->find($id));
        $this->assertNotNull($this->model->find(1));
    }
}

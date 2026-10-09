<?php

use App\Models\Tenant\TenantModel;

require_once __DIR__ . '/PlatformEndpointTestCase.php';

/**
 * Suspender un Tenant desde el panel corta todas sus rutas con 403, sin tocar a los demás ni borrar
 * datos; reactivarlo lo restituye.
 *
 * @internal
 */
final class TenantSuspensionTest extends PlatformEndpointTestCase
{
    private string $token;
    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->platformToken();
        $this->db->table('tenants')->insert(['slug' => 'clinica-sur', 'name' => 'Clínica Sur', 'status' => 'active']);
        $this->tenantId = $this->db->insertID();
    }

    private function setStatus(int $id, string $status)
    {
        return $this->withToken($this->token)->fresh()->withBodyFormat('json')->put("platform/tenants/{$id}", ['status' => $status]);
    }

    private function asTenant(string $slug)
    {
        return $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => $slug]);
    }

    public function testSuspendReturns200WithTheNewStatus(): void
    {
        $result = $this->setStatus($this->tenantId, 'suspended');

        $result->assertStatus(200);
        $this->assertSame('suspended', $this->decode($result)['status']);
        $this->assertTrue((new TenantModel())->find($this->tenantId)->isSuspended());
    }

    public function testASuspendedTenantAnswers403OnItsPublicRoutes(): void
    {
        $this->asTenant('clinica-sur')->get('public/specialties')->assertStatus(200);

        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $this->asTenant('clinica-sur')->get('public/specialties')->assertStatus(403);
    }

    public function testASuspendedTenantAnswers403OnItsPrivateRoutesAndOnLogin(): void
    {
        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $this->asTenant('clinica-sur')->get('users')->assertStatus(403);
        $this->asTenant('clinica-sur')->withBodyFormat('json')->post('login', ['email' => 'a@b.co', 'password' => 'x'])->assertStatus(403);
    }

    public function testSuspendingOneTenantDoesNotAffectTheOthers(): void
    {
        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $this->asTenant('demo')->get('public/specialties')->assertStatus(200);
        $this->assertFalse((new TenantModel())->find(1)->isSuspended());
    }

    public function testSuspendingKeepsTheTenantData(): void
    {
        $this->db->table('specialties')->insert(['tenant_id' => $this->tenantId, 'name' => 'Cardiología']);

        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $count = $this->db->query('SELECT COUNT(*) AS c FROM specialties WHERE tenant_id = ?', [$this->tenantId])->getRow()->c;
        $this->assertSame(1, (int) $count);
    }

    public function testReactivatingRestoresTheTenant(): void
    {
        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);
        $this->asTenant('clinica-sur')->get('public/specialties')->assertStatus(403);

        $this->setStatus($this->tenantId, 'active')->assertStatus(200);

        $this->asTenant('clinica-sur')->get('public/specialties')->assertStatus(200);
    }

    public function testASuspendedTenantCanStillBeManagedFromThePanel(): void
    {
        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $list = $this->withToken($this->token)->fresh()->get('platform/tenants?status=suspended');
        $list->assertStatus(200);
        $this->assertSame(['clinica-sur'], array_column($this->decode($list)['data'], 'slug'));

        $rename = $this->withToken($this->token)->fresh()->withBodyFormat('json')->put("platform/tenants/{$this->tenantId}", ['name' => 'Clínica Sur SA']);
        $rename->assertStatus(200);
        $this->assertSame('suspended', $this->decode($rename)['status']);
    }

    public function testSuspendedTenantsAreSkippedByTheProcessesThatLoopOverTenants(): void
    {
        $this->setStatus($this->tenantId, 'suspended')->assertStatus(200);

        $ids = array_map(static fn ($t) => $t->getId(), (new TenantModel())->findAllActive());

        $this->assertSame([1], $ids);
    }
}

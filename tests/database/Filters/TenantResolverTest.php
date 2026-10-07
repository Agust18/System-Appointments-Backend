<?php

use App\Constants\TenantStatus;
use App\Entity\Tenant\Tenant;
use App\Filters\TenantResolver;
use App\Tenancy\TenantContext;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class TenantResolverTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private TenantResolver $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetServices();
        TenantContext::clear();
        $this->setFallbackSlug('');
        $this->filter = new TenantResolver();
    }

    protected function tearDown(): void
    {
        // Restaura el Tenant por defecto que fija tests/bootstrap.php y el fallback de phpunit.xml.dist.
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
        $this->setFallbackSlug('demo');
        $this->resetServices();
        parent::tearDown();
    }

    private function setFallbackSlug(string $slug): void
    {
        $_ENV['tenant.fallbackSlug'] = $slug;
        $_SERVER['tenant.fallbackSlug'] = $slug;
    }

    private function seedTenant(string $slug, string $status = TenantStatus::ACTIVE): int
    {
        $this->db->table('tenants')->insert([
            'slug'   => $slug,
            'name'   => 'Tenant ' . $slug,
            'status' => $status,
        ]);

        return $this->db->insertID();
    }

    private function requestWithTenant(?string $slug, string $method = 'get')
    {
        $request = Services::request(null, false)->withMethod($method);

        if ($slug !== null) {
            $request->setHeader('X-Tenant', $slug);
        }

        return $request;
    }

    public function testMissingHeaderReturns400(): void
    {
        $result = $this->filter->before($this->requestWithTenant(null));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(400, $result->getStatusCode());
        $this->assertFalse(TenantContext::isSet());
    }

    public function testMissingHeaderUsesTheFallbackSlugOutsideProduction(): void
    {
        $this->setFallbackSlug('demo');

        $result = $this->filter->before($this->requestWithTenant(null));

        $this->assertNotInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(1, TenantContext::id());
    }

    public function testExplicitHeaderWinsOverTheFallback(): void
    {
        $this->setFallbackSlug('demo');
        $id = $this->seedTenant('tenant-a');

        $this->filter->before($this->requestWithTenant('tenant-a'));

        $this->assertSame($id, TenantContext::id());
    }

    public function testOptionsPreflightPassesWithoutTenant(): void
    {
        $result = $this->filter->before($this->requestWithTenant(null, 'options'));

        $this->assertNotInstanceOf(ResponseInterface::class, $result);
        $this->assertFalse(TenantContext::isSet());
    }

    public function testUnknownSlugReturns404(): void
    {
        $result = $this->filter->before($this->requestWithTenant('no-existe'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertFalse(TenantContext::isSet());
    }

    public function testInvalidSlugFormatReturns404(): void
    {
        $result = $this->filter->before($this->requestWithTenant("a'; DROP TABLE users;--"));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }

    public function testSuspendedTenantReturns403(): void
    {
        $this->seedTenant('suspendido', TenantStatus::SUSPENDED);

        $result = $this->filter->before($this->requestWithTenant('suspendido'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
        $this->assertFalse(TenantContext::isSet());
    }

    public function testActiveTenantFillsTheContextAndLetsTheRequestThrough(): void
    {
        $id = $this->seedTenant('tenant-a');

        $result = $this->filter->before($this->requestWithTenant('tenant-a'));

        $this->assertNotInstanceOf(ResponseInterface::class, $result);
        $this->assertSame($id, TenantContext::id());
    }

    public function testSlugIsCaseInsensitive(): void
    {
        $id = $this->seedTenant('tenant-a');

        $this->filter->before($this->requestWithTenant('Tenant-A'));

        $this->assertSame($id, TenantContext::id());
    }
}

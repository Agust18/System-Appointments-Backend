<?php

use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotResolvedException;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TenantContextTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        // Restaura el Tenant por defecto que fija tests/bootstrap.php para el resto de la suite.
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
        parent::tearDown();
    }

    public function testIdFailsWhenNobodySetTheTenant(): void
    {
        $this->expectException(TenantNotResolvedException::class);

        TenantContext::id();
    }

    public function testIdReturnsTheTenantIdOnceSet(): void
    {
        TenantContext::set(new Tenant(7, 'tenant-a', 'Tenant A', 'active', null, null));

        $this->assertTrue(TenantContext::isSet());
        $this->assertSame(7, TenantContext::id());
    }

    public function testClearRemovesTheTenant(): void
    {
        TenantContext::set(new Tenant(7, 'tenant-a', 'Tenant A', 'active', null, null));
        TenantContext::clear();

        $this->assertFalse(TenantContext::isSet());
    }
}

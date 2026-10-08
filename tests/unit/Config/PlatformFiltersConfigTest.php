<?php

use CodeIgniter\Filters\Filters;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * El filtro global `tenant` no debe correr en /platform/* (SuperAdmin, sin Tenant) y sí en el resto.
 *
 * @internal
 */
final class PlatformFiltersConfigTest extends CIUnitTestCase
{
    /** @return string[] alias de los filtros 'before' que corren para la ruta */
    private function beforeFiltersFor(string $uri): array
    {
        $filters = new Filters(config('Filters'), Services::request(null, false), Services::response(null, false));

        return $filters->initialize($uri)->getFilters()['before'];
    }

    /** @return array<string, array{string}> */
    public static function platformPaths(): array
    {
        return [
            'login'            => ['platform/login'],
            'tenants'          => ['platform/tenants'],
            'tenant por id'    => ['platform/tenants/3'],
            'platform a secas' => ['platform'],
        ];
    }

    /** @dataProvider platformPaths */
    public function testTenantFilterDoesNotRunOnPlatformRoutes(string $uri): void
    {
        $this->assertNotContains('tenant', $this->beforeFiltersFor($uri));
    }

    /** @return array<string, array{string}> */
    public static function tenantPaths(): array
    {
        return [
            'users'               => ['users'],
            'appointments'        => ['appointments/5'],
            'login de tenant'     => ['login'],
            'public'              => ['public/specialties'],
            'prefijo parecido'    => ['platforms/tenants'],
            'platform no al inicio' => ['users/platform/login'],
        ];
    }

    /** @dataProvider tenantPaths */
    public function testTenantFilterStillRunsOnEveryOtherRoute(string $uri): void
    {
        $this->assertContains('tenant', $this->beforeFiltersFor($uri));
    }

    public function testPlatformAdminOnlyIsRegisteredAsAnAlias(): void
    {
        $this->assertSame(\App\Filters\PlatformAdminOnly::class, config('Filters')->aliases['platformAdminOnly']);
    }
}

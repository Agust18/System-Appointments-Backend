<?php

use CodeIgniter\Commands\Utilities\Routes\FilterCollector;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Filtros efectivos de las rutas de /platform/* (los mismos que muestra `php spark filter:check`,
 * globales incluidos). Son la seguridad del panel: una ruta privada sin 'platformAdminOnly' quedaría
 * abierta, y con 'tenant' respondería 400 en producción.
 *
 * @internal
 */
final class PlatformRoutesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetServices();
        service('routes')->loadRoutes();
    }

    protected function tearDown(): void
    {
        $this->resetServices();
        parent::tearDown();
    }

    /** @return string[] filtros 'before' efectivos de la ruta */
    private function before(string $method, string $uri): array
    {
        return (new FilterCollector())->get($method, $uri)['before'];
    }

    /** @return array<string, array{string, string}> */
    public static function privateRoutes(): array
    {
        return [
            'listar'                => ['GET', 'platform/tenants'],
            'crear'                 => ['POST', 'platform/tenants'],
            'suspender / reactivar' => ['PUT', 'platform/tenants/7'],
            'leer settings'         => ['GET', 'platform/tenants/7/settings'],
            'editar settings'       => ['PUT', 'platform/tenants/7/settings'],
        ];
    }

    /** @dataProvider privateRoutes */
    public function testPrivateRoutesRunThePlatformFilterAndNeverAuthNorTenant(string $method, string $uri): void
    {
        $before = $this->before($method, $uri);

        $this->assertContains('platformAdminOnly', $before);
        $this->assertNotContains('auth', $before);
        $this->assertNotContains('adminOnly', $before);
        $this->assertNotContains('tenant', $before);
    }

    /** @dataProvider privateRoutes */
    public function testCorsRunsBeforeThePlatformFilter(string $method, string $uri): void
    {
        $before = $this->before($method, $uri);

        $this->assertContains('cors:platform', $before);
        $this->assertLessThan(
            array_search('platformAdminOnly', $before, true),
            array_search('cors:platform', $before, true)
        );
    }

    public function testLoginIsPublicButCorsAndThrottledAndHasNoTenant(): void
    {
        $before = $this->before('POST', 'platform/login');

        $this->assertContains('cors:platform', $before);
        $this->assertContains('throttle:10,60', $before);
        $this->assertNotContains('platformAdminOnly', $before);
        $this->assertNotContains('tenant', $before);
    }

    public function testPreflightOnlyRunsCorsAndHasNoTenant(): void
    {
        $before = $this->before('OPTIONS', 'platform/tenants');

        $this->assertContains('cors:platform', $before);
        $this->assertNotContains('tenant', $before);
        $this->assertNotContains('platformAdminOnly', $before);
    }

    public function testThereIsNoDeleteNorPatchUnderPlatform(): void
    {
        foreach (['DELETE', 'PATCH'] as $method) {
            $this->assertSame(['<unknown>'], $this->before($method, 'platform/tenants/7'), "{$method} platform/tenants/7 no debe existir");
            $this->assertSame(['<unknown>'], $this->before($method, 'platform/tenants'), "{$method} platform/tenants no debe existir");
        }
    }

    public function testTenantRoutesKeepTheTenantFilterAndAuth(): void
    {
        $users = $this->before('GET', 'users');
        $this->assertContains('tenant', $users);
        $this->assertContains('auth', $users);
        $this->assertNotContains('platformAdminOnly', $users);

        $this->assertContains('tenant', $this->before('POST', 'login'));
        $this->assertContains('tenant', $this->before('GET', 'public/specialties'));
    }

    public function testTenantConfigIsPublicButCorsAndThrottledAndHasTenant(): void
    {
        $before = $this->before('GET', 'tenant/config');

        $this->assertContains('tenant', $before);
        $this->assertNotContains('auth', $before);
        $this->assertNotContains('platformAdminOnly', $before);
        $this->assertContains('cors', $before);
        $this->assertContains('throttle:60,60', $before);
        $this->assertLessThan(array_search('throttle:60,60', $before, true), array_search('cors', $before, true));
    }

    public function testConsultaRoutesArePublicButCorsThrottledAndHaveTenant(): void
    {
        $expected = [
            'public/appointments/lookup' => 'throttle:10,60',
            'public/appointments/cancel' => 'throttle:5,60',
        ];

        foreach ($expected as $uri => $throttle) {
            $before = $this->before('POST', $uri);

            $this->assertContains('tenant', $before, $uri);
            $this->assertNotContains('auth', $before, $uri);
            $this->assertContains('cors', $before, $uri);
            $this->assertContains($throttle, $before, $uri);
            $this->assertLessThan(array_search($throttle, $before, true), array_search('cors', $before, true), $uri);
        }
    }

    public function testRegeneratingAnAccessCodeRequiresAdminOrReception(): void
    {
        $before = $this->before('POST', 'appointments/7/access-code');

        $this->assertContains('tenant', $before);
        $this->assertContains('auth', $before);
        $this->assertContains('adminOrReception', $before);
    }

    public function testTheTenantCannotEditItsOwnConfig(): void
    {
        // La configuración la carga solo el panel (/platform/tenants/{id}/settings): GET es la única ruta de tenant/config.
        foreach (['POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->assertSame(['<unknown>'], $this->before($method, 'tenant/config'), "{$method} tenant/config no debe existir");
        }
    }

    public function testNoTenantRouteWasLeftWithoutItsAuthFilter(): void
    {
        // Todo lo que no es /public, /platform ni un flujo de acceso (login, registro, verificación y reset) debe pasar por 'auth'.
        // 'tenant/config' es pública a propósito (la lee el login); su exención se revisa en testTenantConfigIsPublicButCorsAndThrottled.
        $open = ['/', 'login', 'forgot-password', 'reset-password', 'tenant/config'];
        $checked = 0;

        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $verb) {
            foreach (array_keys(service('routes')->getRoutes($verb)) as $route) {
                if (in_array($route, $open, true) || str_starts_with($route, 'public/') || str_starts_with($route, 'platform/')) {
                    continue;
                }

                // Las rutas con placeholders se prueban con un id concreto.
                $uri = preg_replace('/\(\[0-9\]\+\)|\(:num\)/', '1', $route);
                $this->assertContains('auth', $this->before($verb, $uri), "{$verb} {$route} quedó sin 'auth'");
                $this->assertContains('tenant', $this->before($verb, $uri), "{$verb} {$route} quedó sin 'tenant'");
                $checked++;
            }
        }

        $this->assertGreaterThan(30, $checked, 'El recorrido no revisó rutas: el test sería vacío.');
    }
}

<?php

use App\Exception\Validation\ValidationException;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

require_once __DIR__ . '/PlatformEndpointTestCase.php';

/**
 * Throttle por IP de POST /platform/login (10 por minuto) sobre el router y los filtros reales.
 *
 * Se dispara con un body vacío: el throttle corre antes que el controller, así que los requests
 * dentro del límite terminan en ValidationException (422, que sube al test) sin tocar la base, y el
 * que excede el límite es el único que devuelve una respuesta 429. En tests el cache es 'dummy', por
 * eso se inyecta un MockCache propio, el MISMO entre requests para que el bucket conserve su estado.
 *
 * @internal
 */
final class PlatformLoginThrottleTest extends PlatformEndpointTestCase
{
    private MockCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MockCache();
    }

    private function postLogin()
    {
        $this->resetServices();
        Services::injectMock('cache', $this->cache);

        try {
            return $this->withBodyFormat('json')->post('platform/login', []);
        } catch (ValidationException) {
            return null;
        }
    }

    public function testTheTenthRequestPassesAndTheEleventhIs429(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->assertNull($this->postLogin(), "El request {$i} debía pasar el throttle");
        }

        $blocked = $this->postLogin();

        $this->assertNotNull($blocked, 'El request 11 debía rechazarlo el throttle');
        $blocked->assertStatus(429);
        $this->assertNotSame('', $blocked->response()->getHeaderLine('Retry-After'));
    }

    public function testTheThrottleDoesNotSpendThePublicBookingQuota(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->postLogin();
        }

        $this->resetServices();
        Services::injectMock('cache', $this->cache);
        $public = $this->get('public/specialties');

        $public->assertStatus(200);
    }
}

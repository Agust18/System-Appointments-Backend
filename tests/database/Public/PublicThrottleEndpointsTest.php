<?php

use App\Exception\Validation\ValidationException;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use CodeIgniter\Test\TestResponse;
use Config\Services;

/**
 * Throttle por IP de /public/* sobre el router y los filtros reales.
 *
 * El POST se dispara con un body vacío: el throttle corre antes que el controller, así que los
 * requests dentro del límite terminan en ValidationException (422, que sube al test) sin escribir
 * nada, y el que excede el límite es el único que devuelve una respuesta 429.
 *
 * En tests el cache es 'dummy'; acá se usa un MockCache propio. Entre requests se resetean los
 * servicios (service('response') es compartido y arrastraría status y headers) volviendo a
 * inyectar el MISMO cache, para que los buckets conserven su estado.
 *
 * @internal
 */
final class PublicThrottleEndpointsTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private const ORIGIN = 'https://clinica.example';
    private const ENV_KEYS = ['cors.allowedOrigins', 'cors.allowedOriginsPatterns'];

    /** @var array<string, mixed> */
    private array $previousEnv = [];

    private MockCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV_KEYS as $key) {
            $this->previousEnv[$key] = $_ENV[$key] ?? null;
            unset($_ENV[$key], $_SERVER[$key]);
        }
        Factories::reset('config');

        $this->cache = new MockCache();
        $this->freshServices();
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $key) {
            if ($this->previousEnv[$key] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $this->previousEnv[$key];
            }
        }
        Factories::reset('config');
        $this->resetServices();

        parent::tearDown();
    }

    private function freshServices(): void
    {
        $this->resetServices();
        Services::injectMock('cache', $this->cache);
    }

    private function allowCorsFor(string $origin): void
    {
        $_ENV['cors.allowedOrigins'] = $origin;
        Factories::reset('config');
    }

    /**
     * Un POST a /public/appointments que cae en validación (422) si el throttle lo deja pasar.
     * Devuelve la respuesta solo si el throttle lo rechazó (429).
     */
    private function postBooking(?string $origin = null): ?TestResponse
    {
        $request = $this->withBodyFormat('json');

        if ($origin !== null) {
            $request = $request->withHeaders(['Origin' => $origin]);
        }

        try {
            return $request->post('public/appointments', []);
        } catch (ValidationException) {
            return null;
        }
    }

    private function useUpBookingBucket(?string $origin = null): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->postBooking($origin), "El POST n.º " . ($i + 1) . " no debería estar limitado.");
        }
    }

    // ------------------------------------------------------------------ POST

    public function testTheSixthBookingRequestInAMinuteIsRejectedWith429(): void
    {
        $this->useUpBookingBucket();

        $result = $this->postBooking();

        $this->assertNotNull($result, 'El 6.º POST debería devolver 429.');
        $result->assertStatus(429);
        $this->assertGreaterThanOrEqual(1, (int) $result->response()->getHeaderLine('Retry-After'));
        $this->assertArrayHasKey('error', json_decode($result->getJSON(), true));
    }

    public function testTheRejectionKeepsTheCorsHeadersSoTheBrowserCanReadIt(): void
    {
        $this->allowCorsFor(self::ORIGIN);
        $this->useUpBookingBucket(self::ORIGIN);

        $result = $this->postBooking(self::ORIGIN);

        $this->assertNotNull($result);
        $result->assertStatus(429);
        $this->assertSame(self::ORIGIN, $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testPreflightRequestsDoNotConsumeTheBookingBucket(): void
    {
        $this->allowCorsFor(self::ORIGIN);

        for ($i = 0; $i < 10; $i++) {
            $preflight = $this->withHeaders([
                'Origin'                        => self::ORIGIN,
                'Access-Control-Request-Method' => 'POST',
            ])->call('options', 'public/appointments');

            $this->assertSame(204, $preflight->response()->getStatusCode());
            $this->freshServices();
        }

        // Tras 10 preflights el cupo del POST sigue intacto: 5 pasan y el 6.º se rechaza.
        $this->useUpBookingBucket(self::ORIGIN);
        $this->assertNotNull($this->postBooking(self::ORIGIN));
    }

    // ------------------------------------------------------------------- GET

    /**
     * El Throttler es un token bucket: 60 por minuto = ráfaga de 60 y después 1 por segundo.
     * Sin congelar el reloj, los segundos que tardan 61 requests recargan el bucket y el
     * último pasaría.
     */
    private function freezeThrottleClock(): void
    {
        service('throttler')->setTestTime(time());
    }

    public function testReadsAreLimitedTo60PerMinute(): void
    {
        $this->freezeThrottleClock();

        for ($i = 1; $i <= 60; $i++) {
            $this->get('public/specialties')->assertStatus(200);
        }

        $this->get('public/specialties')->assertStatus(429);
    }

    public function testReadsAndBookingsHaveSeparateBuckets(): void
    {
        $this->useUpBookingBucket();
        $this->assertNotNull($this->postBooking());

        // POST agotado: las lecturas siguen funcionando.
        $this->freshServices();
        $this->get('public/specialties')->assertStatus(200);
    }

    public function testBookingRequestsDoNotConsumeTheReadBucket(): void
    {
        $this->useUpBookingBucket();
        $this->freshServices();

        for ($i = 1; $i <= 60; $i++) {
            $this->get('public/specialties')->assertStatus(200);
        }
    }

    public function testAllTheReadEndpointsShareTheSameReadBucket(): void
    {
        $this->freezeThrottleClock();

        $this->get('public/specialties')->assertStatus(200);
        $this->get('public/services')->assertStatus(200);
        $this->get('public/medicals/available')->assertStatus(200);

        // 3 lecturas hechas: quedan 57 en el bucket.
        for ($i = 1; $i <= 57; $i++) {
            $this->get('public/specialties')->assertStatus(200);
        }

        $this->get('public/services')->assertStatus(429);
    }

    // ---------------------------------------------------------- rutas privadas

    public function testAuthenticatedRoutesAreNotThrottled(): void
    {
        // Sin token devuelven 401 siempre; ninguna llega a 429 por IP.
        for ($i = 1; $i <= 70; $i++) {
            $this->get('specialties')->assertStatus(401);
        }
    }
}

<?php

use App\Filters\Throttle;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * @internal
 */
final class ThrottleTest extends CIUnitTestCase
{
    private Throttle $filter;

    protected function setUp(): void
    {
        parent::setUp();

        // Cache en memoria y throttler nuevo por test: nada se comparte con otros tests.
        $this->resetServices();
        Services::injectMock('cache', new MockCache());

        $this->filter = new Throttle();
    }

    protected function tearDown(): void
    {
        $this->resetServices();
        parent::tearDown();
    }

    private function request(string $ip = '203.0.113.10', string $method = 'post'): RequestInterface
    {
        // En CLI getIPAddress() ignora $_SERVER y devuelve 0.0.0.0: la IP se inyecta con setGlobal().
        $request = Services::request(null, false);
        $request->setGlobal('server', ['REMOTE_ADDR' => $ip]);

        return $request->withMethod($method);
    }

    private function assertAllowed(mixed $result): void
    {
        $this->assertNotInstanceOf(ResponseInterface::class, $result);
    }

    public function testAllowsRequestsUpToTheCapacity(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->assertAllowed($this->filter->before($this->request(), ['3', '60']));
        }
    }

    public function testRejectsTheRequestThatExceedsTheCapacityWith429(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->filter->before($this->request(), ['3', '60']);
        }

        $result = $this->filter->before($this->request(), ['3', '60']);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(429, $result->getStatusCode());
        $this->assertGreaterThanOrEqual(1, (int) $result->getHeaderLine('Retry-After'));
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertArrayHasKey('error', json_decode($result->getBody(), true));
    }

    public function testEachIpHasItsOwnBucket(): void
    {
        $this->filter->before($this->request('203.0.113.10'), ['1', '60']);

        $this->assertInstanceOf(ResponseInterface::class, $this->filter->before($this->request('203.0.113.10'), ['1', '60']));
        $this->assertAllowed($this->filter->before($this->request('203.0.113.11'), ['1', '60']));
    }

    public function testEachHttpMethodHasItsOwnBucket(): void
    {
        $this->filter->before($this->request('203.0.113.10', 'post'), ['1', '60']);

        $this->assertInstanceOf(ResponseInterface::class, $this->filter->before($this->request('203.0.113.10', 'post'), ['1', '60']));
        $this->assertAllowed($this->filter->before($this->request('203.0.113.10', 'get'), ['1', '60']));
    }

    public function testDifferentLimitsDoNotShareABucket(): void
    {
        $this->filter->before($this->request(), ['1', '60']);

        $this->assertAllowed($this->filter->before($this->request(), ['60', '60']));
    }

    public function testTheBucketRefillsOverTime(): void
    {
        // 3 por 60 s = 1 token cada 20 s.
        for ($i = 0; $i < 3; $i++) {
            $this->filter->before($this->request(), ['3', '60']);
        }
        $this->assertInstanceOf(ResponseInterface::class, $this->filter->before($this->request(), ['3', '60']));

        service('throttler')->setTestTime(time() + 21);

        $this->assertAllowed($this->filter->before($this->request(), ['3', '60']));
        $this->assertInstanceOf(ResponseInterface::class, $this->filter->before($this->request(), ['3', '60']));
    }

    public function testTheRejectionKeepsTheHeadersAlreadySetOnTheSharedResponse(): void
    {
        // El filtro 'cors' corre antes y agrega sus headers a service('response'); el 429 tiene que
        // conservarlos para que el navegador pueda leerlo.
        service('response')->setHeader('Access-Control-Allow-Origin', 'https://clinica.example');

        $this->filter->before($this->request(), ['1', '60']);
        $result = $this->filter->before($this->request(), ['1', '60']);

        $this->assertSame(429, $result->getStatusCode());
        $this->assertSame('https://clinica.example', $result->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * @dataProvider invalidArgumentsProvider
     */
    public function testInvalidArgumentsAreAConfigurationError(?array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->filter->before($this->request(), $arguments);
    }

    public static function invalidArgumentsProvider(): array
    {
        return [
            'sin argumentos'        => [null],
            'solo la capacidad'     => [['5']],
            'capacidad en cero'     => [['0', '60']],
            'segundos en cero'      => [['5', '0']],
            'capacidad no numérica' => [['abc', '60']],
            'capacidad negativa'    => [['-5', '60']],
        ];
    }

    public function testFailsOpenWhenTheCacheDoesNotWork(): void
    {
        $this->resetServices();
        Services::injectMock('cache', new DummyHandler());

        for ($i = 0; $i < 10; $i++) {
            $this->assertAllowed($this->filter->before($this->request(), ['1', '60']));
        }
    }

    public function testAfterDoesNotTouchTheResponse(): void
    {
        $response = service('response');

        $this->assertNull($this->filter->after($this->request(), $response));
    }
}

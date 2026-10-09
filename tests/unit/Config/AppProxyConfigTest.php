<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Services;

/**
 * Los proxies de confianza salen del .env (BaseConfig no puede sobrescribir arrays) y deciden
 * si getIPAddress() cree o ignora el header X-Forwarded-For. De eso depende el throttle por IP.
 *
 * @internal
 */
final class AppProxyConfigTest extends CIUnitTestCase
{
    private const KEYS = ['app.trustedProxies', 'app.proxyHeader'];

    /** @var array<string, mixed> */
    private array $previous = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::KEYS as $key) {
            $this->previous[$key] = $_ENV[$key] ?? null;
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            if ($this->previous[$key] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $this->previous[$key];
            }
        }

        $this->resetServices();
        parent::tearDown();
    }

    /**
     * CodeIgniter pobla los headers del request (X-Forwarded-For, etc.) desde el servicio
     * 'superglobals' al construirlo, y getServer('REMOTE_ADDR') desde sus globals: setGlobal()
     * solo afecta a este último. Por eso se resetean los servicios y se cargan las variables en
     * 'superglobals' ANTES de crear el request; si no, quedan valores de otros tests.
     *
     * @param array<string, string> $server variables de servidor del request
     */
    private function requestFor(array $server): IncomingRequest
    {
        $this->resetServices();
        service('superglobals')->setServerArray($server);

        return Services::incomingrequest(new App(), false);
    }

    public function testWithoutConfiguredProxiesThereAreNone(): void
    {
        $this->assertSame([], (new App())->proxyIPs);
    }

    public function testProxiesAreReadFromTheEnvironmentWithTheDefaultHeader(): void
    {
        $_ENV['app.trustedProxies'] = ' 127.0.0.1 , 172.16.0.0/12 ,,';

        $this->assertSame(
            ['127.0.0.1' => 'X-Forwarded-For', '172.16.0.0/12' => 'X-Forwarded-For'],
            (new App())->proxyIPs
        );
    }

    public function testTheHeaderCanBeConfigured(): void
    {
        $_ENV['app.trustedProxies'] = '10.0.0.5';
        $_ENV['app.proxyHeader'] = 'CF-Connecting-IP';

        $this->assertSame(['10.0.0.5' => 'CF-Connecting-IP'], (new App())->proxyIPs);
    }

    public function testWithoutProxiesTheForwardedHeaderIsIgnored(): void
    {
        $request = $this->requestFor([
            'REMOTE_ADDR'          => '198.51.100.9',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('198.51.100.9', $request->getIPAddress());
    }

    public function testTheClientIpIsTakenFromTheHeaderWhenTheConnectionComesFromATrustedProxy(): void
    {
        $_ENV['app.trustedProxies'] = '10.0.0.5';

        $request = $this->requestFor([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('203.0.113.7', $request->getIPAddress());
    }

    public function testTheHeaderIsIgnoredWhenTheConnectionDoesNotComeFromATrustedProxy(): void
    {
        $_ENV['app.trustedProxies'] = '10.0.0.5';

        // Un cliente cualquiera mandando su propio X-Forwarded-For para evadir el throttle.
        $request = $this->requestFor([
            'REMOTE_ADDR'          => '198.51.100.9',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('198.51.100.9', $request->getIPAddress());
    }

    public function testSubnetsAreSupported(): void
    {
        $_ENV['app.trustedProxies'] = '172.16.0.0/12';

        $request = $this->requestFor([
            'REMOTE_ADDR'          => '172.19.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('203.0.113.7', $request->getIPAddress());
    }

    public function testACustomHeaderIsHonoured(): void
    {
        $_ENV['app.trustedProxies'] = '10.0.0.5';
        $_ENV['app.proxyHeader'] = 'CF-Connecting-IP';

        $request = $this->requestFor([
            'REMOTE_ADDR'            => '10.0.0.5',
            'HTTP_CF_CONNECTING_IP'  => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR'   => '192.0.2.1',
        ]);

        $this->assertSame('203.0.113.7', $request->getIPAddress());
    }

    public function testWhenSeveralIpsAreListedTheFirstOneIsUsed(): void
    {
        // Por eso el proxy debe SOBRESCRIBIR el header: CodeIgniter confía en la primera IP.
        $_ENV['app.trustedProxies'] = '10.0.0.5';

        $request = $this->requestFor([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 198.51.100.9',
        ]);

        $this->assertSame('203.0.113.7', $request->getIPAddress());
    }

    public function testAnInvalidIpInTheHeaderFallsBackToTheConnectionAddress(): void
    {
        $_ENV['app.trustedProxies'] = '10.0.0.5';

        $request = $this->requestFor([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => 'no-es-una-ip',
        ]);

        $this->assertSame('10.0.0.5', $request->getIPAddress());
    }
}

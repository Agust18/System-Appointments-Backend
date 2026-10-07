<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Cors;

/**
 * Los orígenes CORS salen del .env (BaseConfig no puede sobrescribir arrays vacíos).
 *
 * @internal
 */
final class CorsConfigTest extends CIUnitTestCase
{
    private const KEYS = ['cors.allowedOrigins', 'cors.allowedOriginsPatterns'];

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

        parent::tearDown();
    }

    public function testWithoutConfiguredOriginsNoOriginIsAllowed(): void
    {
        $config = (new Cors())->default;

        $this->assertSame([], $config['allowedOrigins']);
        $this->assertSame([], $config['allowedOriginsPatterns']);
    }

    public function testOriginsAreReadFromTheEnvironmentAndTrimmed(): void
    {
        $_ENV['cors.allowedOrigins'] = ' https://clinica-a.com , https://clinica-b.com ,,';

        $config = (new Cors())->default;

        $this->assertSame(['https://clinica-a.com', 'https://clinica-b.com'], $config['allowedOrigins']);
    }

    public function testSingleOriginIsAList(): void
    {
        $_ENV['cors.allowedOrigins'] = 'http://localhost:5173';

        $this->assertSame(['http://localhost:5173'], (new Cors())->default['allowedOrigins']);
    }

    public function testPatternsAreReadFromTheEnvironment(): void
    {
        $_ENV['cors.allowedOriginsPatterns'] = 'https://[a-z0-9-]+\.miclinica\.com';

        $config = (new Cors())->default;

        $this->assertSame(['https://[a-z0-9-]+\.miclinica\.com'], $config['allowedOriginsPatterns']);
        $this->assertSame([], $config['allowedOrigins']);
    }

    public function testDefaultsAllowOnlyWhatThePublicApiNeeds(): void
    {
        $config = (new Cors())->default;

        $this->assertSame(['GET', 'POST', 'OPTIONS'], $config['allowedMethods']);
        $this->assertSame(['Content-Type', 'Accept', 'X-Tenant'], $config['allowedHeaders']);
        $this->assertFalse($config['supportsCredentials']);
    }
}

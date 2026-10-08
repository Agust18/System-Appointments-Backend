<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Cors;

/**
 * Perfil CORS `platform` (filtro `cors:platform` en /platform/*). No toca el perfil `default`.
 *
 * @internal
 */
final class PlatformCorsConfigTest extends CIUnitTestCase
{
    private const KEYS = ['cors.allowedOrigins', 'cors.allowedOriginsPatterns'];

    /** @var array<string, mixed> */
    private array $previous = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::KEYS as $key) {
            $this->previous[$key] = $_ENV[$key] ?? null;
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

    public function testPlatformAllowsPutAndAuthorizationButNotTheTenantHeader(): void
    {
        $platform = (new Cors())->platform;

        $this->assertContains('PUT', $platform['allowedMethods']);
        $this->assertContains('OPTIONS', $platform['allowedMethods']);
        $this->assertContains('Authorization', $platform['allowedHeaders']);
        $this->assertNotContains('X-Tenant', $platform['allowedHeaders']);
        $this->assertFalse($platform['supportsCredentials']);
    }

    public function testPlatformUsesTheSameOriginsAsPublic(): void
    {
        $_ENV['cors.allowedOrigins'] = 'https://panel.test, https://otro.test';
        $_ENV['cors.allowedOriginsPatterns'] = 'https://[a-z0-9-]+\.panel\.test';

        $config = new Cors();

        $this->assertSame(['https://panel.test', 'https://otro.test'], $config->platform['allowedOrigins']);
        $this->assertSame($config->default['allowedOrigins'], $config->platform['allowedOrigins']);
        $this->assertSame($config->default['allowedOriginsPatterns'], $config->platform['allowedOriginsPatterns']);
    }

    public function testDefaultProfileIsUnchangedByThePlatformProfile(): void
    {
        $default = (new Cors())->default;

        $this->assertSame(['GET', 'POST', 'OPTIONS'], $default['allowedMethods']);
        $this->assertSame(['Content-Type', 'Accept', 'X-Tenant'], $default['allowedHeaders']);
    }

    public function testChangingOnePlatformProfileDoesNotLeakIntoDefault(): void
    {
        $config = new Cors();

        $this->assertNotContains('PUT', $config->default['allowedMethods']);
        $this->assertNotContains('Authorization', $config->default['allowedHeaders']);
    }
}

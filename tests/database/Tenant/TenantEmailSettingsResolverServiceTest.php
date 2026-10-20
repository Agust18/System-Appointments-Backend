<?php

use App\Constants\Role;
use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantFrontendUrlNotConfiguredException;
use App\Services\Auth\SendPasswordResetEmailService;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Tests\Support\Database\SeedsTenantEmailSettings;

/**
 * Los emails leen su configuración de tenant_settings del Tenant del contexto: nunca del .env ni de
 * otro Tenant.
 *
 * @internal
 */
final class TenantEmailSettingsResolverServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use SeedsTenantEmailSettings;

    protected $refresh = true;
    protected $namespace = null;

    private mixed $previousFrontendUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousFrontendUrl  = $_ENV['app.frontendUrl'] ?? null;
    }

    protected function tearDown(): void
    {
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
        $this->restoreEnv('app.frontendUrl', $this->previousFrontendUrl);

        parent::tearDown();
    }

    private function restoreEnv(string $key, mixed $value): void
    {
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
        } else {
            $_ENV[$key] = $value;
        }
    }

    private function createTenantWithSettings(string $slug, array $settings): Tenant
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => $slug, 'status' => 'active']);
        $id = $this->db->insertID();
        $this->db->table('tenant_settings')->insert(['tenant_id' => $id, 'name' => $slug] + $settings);

        return new Tenant($id, $slug, $slug, 'active', null, null);
    }

    public function testReplyToPrefersReplyToEmailThenReceptionThenNothing(): void
    {
        $this->seedTenantEmailSettings(1, ['reply_to_email' => 'respuestas@c.test', 'reception_email' => 'recepcion@c.test']);
        $this->assertSame('respuestas@c.test', (new TenantEmailSettingsResolverService())->replyTo());

        $this->seedTenantEmailSettings(1, ['reply_to_email' => '  ', 'reception_email' => 'recepcion@c.test', 'reception_email_enabled' => 0]);
        $this->assertSame('recepcion@c.test', (new TenantEmailSettingsResolverService())->replyTo());

        $this->seedTenantEmailSettings(1, ['reply_to_email' => null, 'reception_email' => null]);
        $this->assertNull((new TenantEmailSettingsResolverService())->replyTo());
    }

    public function testFrontendUrlComesFromTheTenant(): void
    {
        $this->seedTenantEmailSettings(1, ['frontend_url' => 'https://clinica-a.test.local']);

        $this->assertSame('https://clinica-a.test.local', (new TenantEmailSettingsResolverService())->frontendUrl());
    }

    public function testFrontendUrlNeverFallsBackToTheEnvFile(): void
    {
        $_ENV['app.frontendUrl'] = 'http://localhost:5173';
        $this->seedTenantEmailSettings(1, ['frontend_url' => null]);

        try {
            (new TenantEmailSettingsResolverService())->frontendUrl();
            $this->fail('Se esperaba TenantFrontendUrlNotConfiguredException');
        } catch (TenantFrontendUrlNotConfiguredException $e) {
            $this->assertSame(500, $e->getCode());
        }
    }

    public function testTenantWithoutSettingsRowOnlyFailsOnFrontendUrl(): void
    {
        $this->db->query('DELETE FROM tenant_settings WHERE tenant_id = 1');
        $resolver = new TenantEmailSettingsResolverService();

        $this->assertNull($resolver->replyTo());
        $this->assertNull($resolver->receptionEmail());
        $this->assertTrue($resolver->isReceptionEmailEnabled());
        $this->expectException(TenantFrontendUrlNotConfiguredException::class);
        $resolver->frontendUrl();
    }

    public function testEachTenantReadsOnlyItsOwnSettings(): void
    {
        $this->seedTenantEmailSettings(1, ['frontend_url' => 'https://a.test.local', 'reception_email' => 'a@c.test']);
        $tenantB = $this->createTenantWithSettings('b', ['frontend_url' => 'https://b.test.local', 'reception_email' => 'b@c.test']);

        TenantContext::set($tenantB);
        $resolverB = new TenantEmailSettingsResolverService();
        $this->assertSame('https://b.test.local', $resolverB->frontendUrl());
        $this->assertSame('b@c.test', $resolverB->receptionEmail());

        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
        $resolverA = new TenantEmailSettingsResolverService();
        $this->assertSame('https://a.test.local', $resolverA->frontendUrl());
        $this->assertSame('a@c.test', $resolverA->receptionEmail());
    }

    public function testNoTokenIsGeneratedWhenTheTenantHasNoFrontendUrl(): void
    {
        $this->db->table('roles')->insert(['id' => Role::RECEPTION, 'name' => 'reception']);
        $this->seedTenantEmailSettings(1, ['frontend_url' => null]);
        $this->db->table('users')->insert([
            'tenant_id' => 1, 'first_name' => 'Admin', 'last_name' => 'A', 'email' => 'p@a.test',
            'password' => 'hash', 'dni' => 30111333, 'role_id' => Role::RECEPTION,
        ]);
        $user = (new \App\Models\User\UserModel())->find($this->db->insertID());

        foreach ([SendPasswordResetEmailService::class] as $class) {
            try {
                (new $class())->send($user);
                $this->fail("{$class} debía lanzar TenantFrontendUrlNotConfiguredException");
            } catch (TenantFrontendUrlNotConfiguredException) {
                // esperado
            }
        }

        $this->seeNumRecords(0, 'user_verification_tokens', []);
    }
}

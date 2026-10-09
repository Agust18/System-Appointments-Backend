<?php

use App\Constants\Role;

require_once __DIR__ . '/PlatformEndpointTestCase.php';

/**
 * Aislamiento en los dos sentidos entre las sesiones de Tenant y las de plataforma, por HTTP:
 * un token de usuario no entra a /platform/* y un token de plataforma no entra a las rutas de Tenant.
 *
 * @internal
 */
final class PlatformIsolationTest extends PlatformEndpointTestCase
{
    private const USER_TOKEN = 'token-de-un-admin-de-tenant';

    private function seedTenantAdminWithToken(string $token = self::USER_TOKEN): void
    {
        $this->seedAdminRole();
        $this->db->query(
            "INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, is_active, is_verified, role_id, token, token_expiration_date)
             VALUES (1, 'Ana', 'Admin', 'ana@demo.test', 'x', 30123456, 1, 1, ?, ?, '2099-01-01 00:00:00')",
            [Role::ADMIN, $token]
        );
    }

    // ------------------------------- token de usuario de Tenant → /platform/*

    public function testATenantAdminTokenCannotListTenants(): void
    {
        $this->platformToken();
        $this->seedTenantAdminWithToken();

        $this->withToken(self::USER_TOKEN)->fresh()->get('platform/tenants')->assertStatus(401);
    }

    public function testATenantAdminTokenCannotCreateTenants(): void
    {
        $this->platformToken();
        $this->seedTenantAdminWithToken();

        $this->withToken(self::USER_TOKEN)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody())->assertStatus(401);

        $this->assertSame(1, $this->rowsIn('tenants'));
    }

    public function testATenantAdminTokenCannotSuspendTenants(): void
    {
        $this->platformToken();
        $this->seedTenantAdminWithToken();

        $this->withToken(self::USER_TOKEN)->fresh()->withBodyFormat('json')->put('platform/tenants/1', ['status' => 'suspended'])->assertStatus(401);

        $this->assertSame('active', $this->db->query('SELECT status FROM tenants WHERE id = 1')->getRow()->status);
    }

    public function testAMissingTenantAdminTokenAndAForgedOneAnswerTheSame(): void
    {
        $this->platformToken();

        $none = $this->fresh()->get('platform/tenants');
        $forged = $this->withToken('falso')->fresh()->get('platform/tenants');

        $none->assertStatus(401);
        $forged->assertStatus(401);
    }

    // ------------------------------- token de plataforma → rutas de Tenant

    /** @return array<string, array{string, string}> */
    public static function tenantRoutes(): array
    {
        return [
            'listar usuarios'  => ['get', 'users'],
            'ver usuario'      => ['get', 'users/1'],
            'listar turnos'    => ['get', 'appointments'],
            'listar pacientes' => ['get', 'patients'],
            'listar médicos'   => ['get', 'medicals'],
            'métricas'         => ['get', 'metrics/kpis'],
        ];
    }

    /** @dataProvider tenantRoutes */
    public function testAPlatformTokenCannotReachTenantRoutes(string $verb, string $uri): void
    {
        $platformToken = $this->platformToken();

        $result = $this->withToken($platformToken)->fresh()->withHeaders(['X-Tenant' => 'demo'])->{$verb}($uri);

        $this->assertSame(401, $result->response()->getStatusCode(), "{$verb} {$uri} con token de plataforma");
    }

    public function testAPlatformTokenCannotCreateTenantUsers(): void
    {
        $platformToken = $this->platformToken();
        $this->seedAdminRole();

        $this->withToken($platformToken)->fresh()->withHeaders(['X-Tenant' => 'demo'])->withBodyFormat('json')->post('users', [
            'firstName' => 'Intruso', 'lastName' => 'Platform', 'email' => 'intruso@demo.test',
            'password' => 'Intruso-1234', 'dni' => '30999888', 'roleId' => Role::ADMIN,
        ])->assertStatus(401);

        $this->assertSame(0, $this->rowsIn('users'));
    }

    public function testAPlatformTokenDoesNotAuthenticateAsAUserEvenWithTheSameTextInUsers(): void
    {
        // Un token con el mismo texto en `users` es de ese usuario; el de plataforma sigue siendo solo de plataforma.
        $platformToken = $this->platformToken();

        $this->withToken($platformToken)->fresh()->withHeaders(['X-Tenant' => 'demo'])->get('users')->assertStatus(401);
        $this->withToken($platformToken)->fresh()->get('platform/tenants')->assertStatus(200);
    }

    public function testAPlatformSessionDoesNotLeakIntoTenantRequestsInTheSameProcess(): void
    {
        $platformToken = $this->platformToken();

        $this->withToken($platformToken)->fresh()->get('platform/tenants')->assertStatus(200);

        // Misma sesión de PHP, request de Tenant sin token: no hereda nada de la anterior.
        $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => 'demo'])->get('users')->assertStatus(401);
    }

    // ------------------------------------------------------- admin inactivo

    public function testAnInactivePlatformAdminGets403OnEveryPanelRoute(): void
    {
        $token = $this->platformToken(false);

        $this->withToken($token)->fresh()->get('platform/tenants')->assertStatus(403);
        $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody())->assertStatus(403);
        $this->withToken($token)->fresh()->withBodyFormat('json')->put('platform/tenants/1', ['status' => 'suspended'])->assertStatus(403);

        $this->assertSame(1, $this->rowsIn('tenants'));
        $this->assertSame('active', $this->db->query('SELECT status FROM tenants WHERE id = 1')->getRow()->status);
    }

    public function testDeactivatingAnAdminCutsAccessEvenWithAnAlreadyIssuedToken(): void
    {
        $token = $this->platformToken();
        $this->withToken($token)->fresh()->get('platform/tenants')->assertStatus(200);

        $this->db->query('UPDATE platform_admins SET is_active = 0');

        $this->withToken($token)->fresh()->get('platform/tenants')->assertStatus(403);
    }

    public function testAnExpiredPlatformTokenIs401(): void
    {
        $token = $this->platformToken();
        $this->db->query("UPDATE platform_admin_tokens SET expires_at = '2020-01-01 00:00:00'");

        $this->withToken($token)->fresh()->get('platform/tenants')->assertStatus(401);
    }
}

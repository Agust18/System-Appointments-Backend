<?php

use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Config\Factories;

require_once __DIR__ . '/PlatformEndpointTestCase.php';

/**
 * Login, listado, alta y actualización de Tenants por HTTP, con el router y los filtros reales.
 *
 * @internal
 */
final class PlatformEndpointsTest extends PlatformEndpointTestCase
{
    private const ENV_KEYS = ['cors.allowedOrigins', 'cors.allowedOriginsPatterns', 'tenant.fallbackSlug'];

    /** @var array<string, mixed> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV_KEYS as $key) {
            $this->previousEnv[$key] = $_ENV[$key] ?? null;
        }
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $key) {
            if ($this->previousEnv[$key] === null) {
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $_ENV[$key] = $this->previousEnv[$key];
                $_SERVER[$key] = $this->previousEnv[$key];
            }
        }
        Factories::reset('config');

        parent::tearDown();
    }

    private function login(string $email, string $password)
    {
        return $this->fresh()->withBodyFormat('json')->post('platform/login', ['email' => $email, 'password' => $password]);
    }

    /** Sin Tenant resoluble: simula producción (sin X-Tenant y sin fallback el filtro 'tenant' respondería 400). */
    private function withoutAnyTenant(): void
    {
        $_ENV['tenant.fallbackSlug'] = '';
        $_SERVER['tenant.fallbackSlug'] = '';
        TenantContext::clear();
    }

    // ------------------------------------------------------------------ login

    public function testLoginReturnsATokenThatOpensThePanel(): void
    {
        $this->platformToken();
        $this->db->query('DELETE FROM platform_admin_tokens');

        $result = $this->login(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);

        $result->assertStatus(200);
        $body = $this->decode($result);
        $this->assertSame(64, strlen($body['token']));
        $this->assertSame(self::ADMIN_EMAIL, $body['admin']['email']);
        $this->assertArrayNotHasKey('password', $body['admin']);

        $list = $this->withToken($body['token'])->fresh()->get('platform/tenants');
        $list->assertStatus(200);
    }

    public function testLoginWorksWithoutAnyTenantResolved(): void
    {
        $this->platformToken();
        $this->withoutAnyTenant();

        $result = $this->login(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);

        $result->assertStatus(200);
        $this->assertFalse(TenantContext::isSet(), '/platform/* no debe resolver ningún Tenant');
    }

    public function testPanelRoutesWorkWithoutAnyTenantResolved(): void
    {
        $token = $this->platformToken();
        $this->withoutAnyTenant();

        $result = $this->withToken($token)->fresh()->get('platform/tenants');

        $result->assertStatus(200);
        $this->assertFalse(TenantContext::isSet());
    }

    public function testTenantRoutesStillAnswer400WithoutAnyTenant(): void
    {
        // Control del test anterior: la misma condición sí corta una ruta de tenant.
        $this->withoutAnyTenant();

        $result = $this->fresh()->get('public/specialties');

        $result->assertStatus(400);
    }

    public function testWrongPasswordAndUnknownEmailAreTheSame401(): void
    {
        $this->platformToken();

        $wrong = $this->exceptionCodeOf(fn () => $this->login(self::ADMIN_EMAIL, 'incorrecta-123'));
        $unknown = $this->exceptionCodeOf(fn () => $this->login('nadie@plataforma.test', self::ADMIN_PASSWORD));

        $this->assertSame(401, $wrong);
        $this->assertSame(401, $unknown);
    }

    public function testLoginOfAnInactiveAdminIs403(): void
    {
        $this->platformToken(false);

        $code = $this->exceptionCodeOf(fn () => $this->login(self::ADMIN_EMAIL, self::ADMIN_PASSWORD));

        $this->assertSame(403, $code);
    }

    public function testLoginValidatesTheBody(): void
    {
        $this->expectException(ValidationException::class);

        $this->fresh()->withBodyFormat('json')->post('platform/login', ['email' => 'no-es-un-email']);
    }

    // --------------------------------------------------------------- listado

    public function testListRequiresAToken(): void
    {
        $this->fresh()->get('platform/tenants')->assertStatus(401);
    }

    public function testListReturnsTenantsAndTheTotal(): void
    {
        $token = $this->platformToken();
        $this->db->table('tenants')->insert(['slug' => 'clinica-sur', 'name' => 'Clínica Sur', 'status' => 'suspended']);

        $result = $this->withToken($token)->fresh()->get('platform/tenants');

        $result->assertStatus(200);
        $body = $this->decode($result);
        $this->assertSame(2, $body['total']);
        $this->assertSame(['demo', 'clinica-sur'], array_column($body['data'], 'slug'));
    }

    public function testListFiltersByStatusAndSearch(): void
    {
        $token = $this->platformToken();
        $this->db->table('tenants')->insert(['slug' => 'clinica-sur', 'name' => 'Clínica Sur', 'status' => 'suspended']);

        $suspended = $this->decode($this->withToken($token)->fresh()->get('platform/tenants?status=suspended'));
        $byName = $this->decode($this->withToken($token)->fresh()->get('platform/tenants?search=Sur'));

        $this->assertSame(['clinica-sur'], array_column($suspended['data'], 'slug'));
        $this->assertSame(['clinica-sur'], array_column($byName['data'], 'slug'));
    }

    public function testListRejectsAnInvalidStatus(): void
    {
        $token = $this->platformToken();

        $this->expectException(ValidationException::class);

        $this->withToken($token)->fresh()->get('platform/tenants?status=borrado');
    }

    // ------------------------------------------------------------------ alta

    public function testCreateReturns201AndTheTenantWithItsAdmin(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();

        $result = $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody());

        $result->assertStatus(201);
        $body = $this->decode($result);
        $this->assertSame('clinica-sur', $body['slug']);
        $this->assertSame('active', $body['status']);
        $this->assertNotNull((new TenantModel())->findBySlug('clinica-sur'));

        $admin = $this->db->query("SELECT * FROM users WHERE email = 'ana@clinicasur.test'")->getRow();
        $this->assertSame($body['id'], (int) $admin->tenant_id);
    }

    public function testTheNewTenantAdminCanLoginInThatTenant(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();
        $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody())->assertStatus(201);

        $this->withToken(null);
        $login = $this->fresh()->withHeaders(['X-Tenant' => 'clinica-sur'])->withBodyFormat('json')
            ->post('login', ['email' => 'ana@clinicasur.test', 'password' => 'Temporal-123']);

        $login->assertStatus(200);
        $this->assertSame('ana@clinicasur.test', $this->decode($login)['user']['email']);
    }

    public function testTheNewTenantAdminDoesNotExistInOtherTenants(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();
        $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody())->assertStatus(201);

        $this->withToken(null);
        $code = $this->exceptionCodeOf(fn () => $this->fresh()->withHeaders(['X-Tenant' => 'demo'])->withBodyFormat('json')
            ->post('login', ['email' => 'ana@clinicasur.test', 'password' => 'Temporal-123']));

        $this->assertNotNull($code, 'El login en demo debió fallar: ese usuario es de otro Tenant');
        $this->assertNotSame(200, $code);
    }

    public function testCreateNormalizesTheSlugAndTheAdminIdentity(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();

        $result = $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody([
            'slug'       => '  Clinica-Sur ',
            'adminEmail' => ' ANA@ClinicaSur.test ',
            'adminDni'   => '30.123.456',
        ]));

        $result->assertStatus(201);
        $this->assertSame('clinica-sur', $this->decode($result)['slug']);
        $this->assertSame(
            '30123456',
            (string) $this->db->query("SELECT dni FROM users WHERE email = 'ana@clinicasur.test'")->getRow()->dni
        );
    }

    public function testCreateWithARepeatedSlugIs409(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();

        $code = $this->exceptionCodeOf(
            fn () => $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody(['slug' => 'demo']))
        );

        $this->assertSame(409, $code);
        $this->assertSame(1, $this->rowsIn('tenants'));
    }

    public function testCreateWithAReservedSlugIs422(): void
    {
        $token = $this->platformToken();
        $this->seedAdminRole();

        $code = $this->exceptionCodeOf(
            fn () => $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody(['slug' => 'platform']))
        );

        $this->assertSame(422, $code);
        $this->assertSame(1, $this->rowsIn('tenants'));
    }

    public function testCreateRequiresTheAdminData(): void
    {
        $token = $this->platformToken();
        $body = $this->tenantBody();
        unset($body['adminDni'], $body['adminPassword']);

        try {
            $this->withToken($token)->fresh()->withBodyFormat('json')->post('platform/tenants', $body);
            $this->fail('Debió rechazar el body incompleto.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('adminDni', $e->getErrors());
            $this->assertArrayHasKey('adminPassword', $e->getErrors());
        }

        $this->assertSame(1, $this->rowsIn('tenants'));
    }

    public function testCreateWithoutAToken401WritesNothing(): void
    {
        $this->seedAdminRole();

        $this->fresh()->withBodyFormat('json')->post('platform/tenants', $this->tenantBody())->assertStatus(401);

        $this->assertSame(1, $this->rowsIn('tenants'));
        $this->assertSame(0, $this->rowsIn('users'));
    }

    // ---------------------------------------------------------- actualización

    public function testPutRenamesTheTenantAndKeepsItsSlug(): void
    {
        $token = $this->platformToken();
        $this->db->table('tenants')->insert(['slug' => 'clinica-sur', 'name' => 'Clínica Sur', 'status' => 'active']);
        $id = $this->db->insertID();

        $result = $this->withToken($token)->fresh()->withBodyFormat('json')->put("platform/tenants/{$id}", ['name' => 'Clínica Sur SA', 'slug' => 'otro']);

        $result->assertStatus(200);
        $body = $this->decode($result);
        $this->assertSame('Clínica Sur SA', $body['name']);
        $this->assertSame('clinica-sur', $body['slug']);
    }

    public function testPutOfAnUnknownTenantIs404(): void
    {
        $token = $this->platformToken();

        $code = $this->exceptionCodeOf(
            fn () => $this->withToken($token)->fresh()->withBodyFormat('json')->put('platform/tenants/9999', ['name' => 'X1'])
        );

        $this->assertSame(404, $code);
    }

    public function testPutRejectsAnUnknownStatus(): void
    {
        $token = $this->platformToken();

        $this->expectException(ValidationException::class);

        $this->withToken($token)->fresh()->withBodyFormat('json')->put('platform/tenants/1', ['status' => 'deleted']);
    }

    public function testPutRequiresAToken(): void
    {
        $this->fresh()->withBodyFormat('json')->put('platform/tenants/1', ['status' => 'suspended'])->assertStatus(401);

        $this->assertSame('active', $this->db->query('SELECT status FROM tenants WHERE id = 1')->getRow()->status);
    }

    public function testThereIsNoDeleteForTenants(): void
    {
        $token = $this->platformToken();

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        $this->withToken($token)->fresh()->delete('platform/tenants/1');
    }

    // ------------------------------------------------------------------ CORS

    public function testPreflightAllowsPutAndAuthorizationForAConfiguredOrigin(): void
    {
        $_ENV['cors.allowedOrigins'] = 'https://panel.example';
        Factories::reset('config');
        $this->withoutAnyTenant();

        $result = $this->fresh()->withHeaders([
            'Origin'                         => 'https://panel.example',
            'Access-Control-Request-Method'  => 'PUT',
            'Access-Control-Request-Headers' => 'Authorization, Content-Type',
        ])->options('platform/tenants/1');

        $this->assertSame('https://panel.example', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('PUT', $result->response()->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Authorization', $result->response()->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertFalse(TenantContext::isSet(), 'El preflight de /platform no necesita Tenant');
    }

    public function testNoCorsHeadersForAnUnknownOrigin(): void
    {
        // Con más de un origen permitido, uno desconocido no recibe ningún header CORS.
        $_ENV['cors.allowedOrigins'] = 'https://panel.example,https://otro.example';
        Factories::reset('config');

        $result = $this->fresh()->withHeaders(['Origin' => 'https://atacante.example'])->get('platform/tenants');

        $this->assertSame('', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testAnUnknownOriginIsNeverEchoedBack(): void
    {
        // Con un único origen permitido CI4 siempre devuelve ese origen; el navegador bloquea al resto.
        $_ENV['cors.allowedOrigins'] = 'https://panel.example';
        Factories::reset('config');

        $result = $this->fresh()->withHeaders(['Origin' => 'https://atacante.example'])->get('platform/tenants');

        $this->assertNotSame('https://atacante.example', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testWithoutConfiguredOriginsNoCorsHeadersAreSent(): void
    {
        $_ENV['cors.allowedOrigins'] = '';
        $_ENV['cors.allowedOriginsPatterns'] = '';
        Factories::reset('config');

        $result = $this->fresh()->withHeaders(['Origin' => 'https://panel.example'])->get('platform/tenants');

        $this->assertSame('', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testTheCorsHeadersAlsoReachA401(): void
    {
        $_ENV['cors.allowedOrigins'] = 'https://panel.example';
        Factories::reset('config');

        $result = $this->fresh()->withHeaders(['Origin' => 'https://panel.example'])->get('platform/tenants');

        $result->assertStatus(401);
        $this->assertSame('https://panel.example', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }
}

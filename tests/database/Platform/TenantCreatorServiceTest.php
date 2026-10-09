<?php

use App\Constants\Role;
use App\Constants\TenantStatus;
use App\Dto\Request\Auth\ResetPasswordRequest;
use App\Dto\Request\Auth\UserLoginRequest;
use App\Dto\Request\Platform\TenantRequest;
use App\Exception\Platform\TenantSlugAlreadyExistsException;
use App\Exception\Role\RoleNotFoundException;
use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantModel;
use App\Services\Auth\ResetPasswordService;
use App\Services\Auth\UserLoginService;
use App\Services\Platform\Tenant\TenantAdminWelcomeTokenService;
use App\Services\Platform\Tenant\TenantCreatorService;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Tests\Support\Email\DisablesSmtp;

/**
 * El SMTP queda deshabilitado: el mail de bienvenida siempre falla rápido (nunca sale un mail real)
 * y el alta debe seguir en pie con `welcomeEmailSent = false`.
 *
 * @internal
 */
final class TenantCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use DisablesSmtp;

    protected $refresh = true;
    protected $namespace = null;

    private ?\App\Entity\Tenant\Tenant $originalContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableSmtp();
        $this->originalContext = TenantContext::current();
    }

    protected function tearDown(): void
    {
        // El mismo email/DNI en dos Tenants impide revertir la migración AddTenantIdToUsersAndPatients
        // (limitación del ADR 003): no dejar datos entre clases.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('DELETE FROM users WHERE tenant_id <> 1');
        $this->db->query('DELETE FROM tenants WHERE id <> 1');
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->originalContext ? TenantContext::set($this->originalContext) : TenantContext::clear();
        $this->restoreSmtp();
        parent::tearDown();
    }

    private function request(
        string $slug = 'clinica-sur',
        string $email = 'ana@clinicasur.test',
        int $dni = 30123456,
        ?string $password = 'Temporal-123',
        string $frontendUrl = 'https://clinica-sur.test.local'
    ): TenantRequest {
        return new TenantRequest($slug, 'Clínica Sur', 'Ana', 'Pérez', $email, $dni, $password, $frontendUrl);
    }

    private function service(): TenantCreatorService
    {
        return new TenantCreatorService();
    }

    private function seedAdminRole(): void
    {
        $this->db->table('roles')->insert(['id' => Role::ADMIN, 'name' => 'admin']);
    }

    private function countRows(string $table): int
    {
        return (int) $this->db->query("SELECT COUNT(*) AS c FROM {$table}")->getRow()->c;
    }

    public function testCreatesTheTenantActiveWithItsAdmin(): void
    {
        $this->seedAdminRole();

        $response = $this->service()->create($this->request());

        $this->assertSame('clinica-sur', $response->slug);
        $this->assertSame(TenantStatus::ACTIVE, $response->status);

        $user = $this->db->query("SELECT * FROM users WHERE email = 'ana@clinicasur.test'")->getRow();
        $this->assertSame($response->id, (int) $user->tenant_id);
        $this->assertSame(Role::ADMIN, (int) $user->role_id);
        $this->assertSame('1', (string) $user->is_verified);
        $this->assertSame('1', (string) $user->is_active);
        $this->assertSame(30123456, (int) $user->dni);
        $this->assertTrue(password_verify('Temporal-123', $user->password));
    }

    public function testTheAdminCanLoginInItsOwnTenantOnly(): void
    {
        $this->seedAdminRole();
        $response = $this->service()->create($this->request());

        $inNew = $this->db->query("SELECT COUNT(*) AS c FROM users WHERE email = ? AND tenant_id = ?", ['ana@clinicasur.test', $response->id])->getRow()->c;
        $inDemo = $this->db->query("SELECT COUNT(*) AS c FROM users WHERE email = ? AND tenant_id = 1", ['ana@clinicasur.test'])->getRow()->c;

        $this->assertSame(1, (int) $inNew);
        $this->assertSame(0, (int) $inDemo);
    }

    public function testRestoresThePreviousTenantContext(): void
    {
        $this->seedAdminRole();
        $before = TenantContext::current();

        $this->service()->create($this->request());

        $this->assertSame($before?->getId(), TenantContext::current()?->getId());
    }

    public function testLeavesTheContextEmptyWhenItStartedEmpty(): void
    {
        $this->seedAdminRole();
        TenantContext::clear();

        $this->service()->create($this->request());

        $this->assertFalse(TenantContext::isSet());
    }

    public function testWelcomeTokenBelongsToTheNewTenantAndLastsSeventyTwoHours(): void
    {
        $this->seedAdminRole();

        $response = $this->service()->create($this->request());

        $token = $this->db->query('SELECT * FROM user_verification_tokens')->getRow();
        $this->assertSame($response->id, (int) $token->tenant_id);
        $this->assertSame('password_reset', $token->type);
        $hours = (strtotime($token->expires_at) - time()) / 3600;
        $this->assertGreaterThan(TenantAdminWelcomeTokenService::TTL_HOURS - 0.1, $hours);
        $this->assertLessThanOrEqual(TenantAdminWelcomeTokenService::TTL_HOURS + 0.1, $hours);
    }

    public function testWithoutAdminPasswordTheAdminChoosesItWithTheEmailLink(): void
    {
        $this->seedAdminRole();
        $response = $this->service()->create($this->request(password: null));

        $user = $this->db->query("SELECT * FROM users WHERE email = 'ana@clinicasur.test'")->getRow();
        $this->assertNull($user->password);
        $this->assertSame('1', (string) $user->is_verified);

        // El token del link de bienvenida se genera antes del envío: queda en la base aunque el mail falle.
        $token = $this->db->query('SELECT token FROM user_verification_tokens')->getRow()->token;
        TenantContext::set((new TenantModel())->findBySlug('clinica-sur'));
        (new ResetPasswordService())->reset(new ResetPasswordRequest($token, 'MiClaveNueva-99'));

        $login = (new UserLoginService())->login(new UserLoginRequest('ana@clinicasur.test', 'MiClaveNueva-99'));
        $this->assertSame($response->id, (int) $user->tenant_id);
        $this->assertNotNull($login);
    }

    public function testSmtpFailureKeepsTheTenantAndReportsFalse(): void
    {
        $this->seedAdminRole();

        $response = $this->service()->create($this->request());

        $this->assertFalse($response->welcomeEmailSent);
        $this->assertNotNull((new TenantModel())->findBySlug('clinica-sur'));
        $this->assertSame(1, $this->countRows('users'));
    }

    public function testNoEmailNorTokenWhenCreationFails(): void
    {
        // Sin el rol ADMIN sembrado el alta falla y se deshace todo.
        try {
            $this->service()->create($this->request());
            $this->fail('Debió fallar por el rol ADMIN inexistente.');
        } catch (RoleNotFoundException) {
        }

        $this->assertSame(0, $this->countRows('user_verification_tokens'));
    }

    public function testInvalidFrontendUrlIs422AndWritesNothing(): void
    {
        $this->seedAdminRole();
        $tenants = $this->countRows('tenants');

        foreach (['sin-esquema.test', 'https://clinica.test/ruta', 'javascript:alert(1)'] as $url) {
            try {
                $this->service()->create($this->request(frontendUrl: $url));
                $this->fail("Debió rechazar {$url}.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('frontendUrl', $e->getErrors());
            }
        }

        $this->assertSame($tenants, $this->countRows('tenants'));
        $this->assertSame(0, $this->countRows('users'));
    }

    public function testWelcomeEmailRestoresTheContextWhenItStartedEmpty(): void
    {
        $this->seedAdminRole();
        TenantContext::clear();

        $this->service()->create($this->request());

        $this->assertFalse(TenantContext::isSet());
    }

    public function testDuplicateSlugIs409AndWritesNothing(): void
    {
        $this->seedAdminRole();
        $tenants = $this->countRows('tenants');
        $users = $this->countRows('users');

        try {
            $this->service()->create($this->request('demo'));
            $this->fail('Debió rechazar el slug repetido.');
        } catch (TenantSlugAlreadyExistsException $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->assertSame($tenants, $this->countRows('tenants'));
        $this->assertSame($users, $this->countRows('users'));
    }

    public function testReservedSlugIs422(): void
    {
        $this->seedAdminRole();

        try {
            $this->service()->create($this->request('platform'));
            $this->fail('Debió rechazar el slug reservado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slug', $e->getErrors());
        }

        $this->assertNull((new TenantModel())->findBySlug('platform'));
    }

    public function testAdminCreationFailureRollsTheTenantBack(): void
    {
        // Sin el rol ADMIN sembrado, UserCreatorService falla DESPUÉS de insertar el Tenant.
        $tenants = $this->countRows('tenants');

        try {
            $this->service()->create($this->request());
            $this->fail('Debió fallar por el rol ADMIN inexistente.');
        } catch (RoleNotFoundException) {
        }

        $this->assertSame($tenants, $this->countRows('tenants'));
        $this->assertNull((new TenantModel())->findBySlug('clinica-sur'));
        $this->assertSame(0, $this->countRows('users'));
        // La fila de settings se inserta antes que el ADMIN: el rollback también la deshace (solo queda la de demo).
        $this->assertSame(1, $this->countRows('tenant_settings'));
    }

    public function testCreatesTheSettingsRowWithItsFrontendUrlAndDefaults(): void
    {
        $this->seedAdminRole();

        $response = $this->service()->create($this->request());

        $row = $this->db->query('SELECT * FROM tenant_settings WHERE tenant_id = ?', [$response->id])->getRow();
        $this->assertNotNull($row);
        $this->assertSame('Clínica Sur', $row->name);
        $this->assertNull($row->reply_to_email);
        $this->assertSame('https://clinica-sur.test.local', $row->frontend_url);
        $this->assertNull($row->reception_email);
        $this->assertSame('1', (string) $row->reception_email_enabled);
    }

    public function testContextIsRestoredEvenWhenCreationFails(): void
    {
        $before = TenantContext::current();

        try {
            $this->service()->create($this->request());
        } catch (RoleNotFoundException) {
        }

        $this->assertSame($before?->getId(), TenantContext::current()?->getId());
    }

    public function testTheSameEmailAndDniCanBeUsedInAnotherTenant(): void
    {
        $this->seedAdminRole();
        $service = $this->service();

        $service->create($this->request('clinica-sur'));
        $service->create($this->request('clinica-norte'));

        $this->assertSame(2, $this->countRows('users'));
    }
}

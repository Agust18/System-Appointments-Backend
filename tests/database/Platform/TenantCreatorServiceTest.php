<?php

use App\Constants\Role;
use App\Constants\TenantStatus;
use App\Dto\Request\Platform\TenantRequest;
use App\Exception\Platform\TenantSlugAlreadyExistsException;
use App\Exception\Role\RoleNotFoundException;
use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantModel;
use App\Services\Platform\Tenant\TenantCreatorService;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class TenantCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private ?\App\Entity\Tenant\Tenant $originalContext;

    protected function setUp(): void
    {
        parent::setUp();
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
        parent::tearDown();
    }

    private function request(string $slug = 'clinica-sur', string $email = 'ana@clinicasur.test', int $dni = 30123456): TenantRequest
    {
        return new TenantRequest($slug, 'Clínica Sur', 'Ana', 'Pérez', $email, $dni, 'Temporal-123');
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

        $response = (new TenantCreatorService())->create($this->request());

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
        $response = (new TenantCreatorService())->create($this->request());

        $inNew = $this->db->query("SELECT COUNT(*) AS c FROM users WHERE email = ? AND tenant_id = ?", ['ana@clinicasur.test', $response->id])->getRow()->c;
        $inDemo = $this->db->query("SELECT COUNT(*) AS c FROM users WHERE email = ? AND tenant_id = 1", ['ana@clinicasur.test'])->getRow()->c;

        $this->assertSame(1, (int) $inNew);
        $this->assertSame(0, (int) $inDemo);
    }

    public function testRestoresThePreviousTenantContext(): void
    {
        $this->seedAdminRole();
        $before = TenantContext::current();

        (new TenantCreatorService())->create($this->request());

        $this->assertSame($before?->getId(), TenantContext::current()?->getId());
    }

    public function testLeavesTheContextEmptyWhenItStartedEmpty(): void
    {
        $this->seedAdminRole();
        TenantContext::clear();

        (new TenantCreatorService())->create($this->request());

        $this->assertFalse(TenantContext::isSet());
    }

    public function testNoEmailIsSentOnCreation(): void
    {
        $this->seedAdminRole();

        (new TenantCreatorService())->create($this->request());

        $this->assertSame(0, $this->countRows('user_verification_tokens'));
    }

    public function testDuplicateSlugIs409AndWritesNothing(): void
    {
        $this->seedAdminRole();
        $tenants = $this->countRows('tenants');
        $users = $this->countRows('users');

        try {
            (new TenantCreatorService())->create($this->request('demo'));
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
            (new TenantCreatorService())->create($this->request('platform'));
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
            (new TenantCreatorService())->create($this->request());
            $this->fail('Debió fallar por el rol ADMIN inexistente.');
        } catch (RoleNotFoundException) {
        }

        $this->assertSame($tenants, $this->countRows('tenants'));
        $this->assertNull((new TenantModel())->findBySlug('clinica-sur'));
        $this->assertSame(0, $this->countRows('users'));
        // La fila de settings se inserta antes que el ADMIN: el rollback también la deshace (solo queda la de demo).
        $this->assertSame(1, $this->countRows('tenant_settings'));
    }

    public function testCreatesTheSettingsRowWithDefaults(): void
    {
        $this->seedAdminRole();

        $response = (new TenantCreatorService())->create($this->request());

        $row = $this->db->query('SELECT * FROM tenant_settings WHERE tenant_id = ?', [$response->id])->getRow();
        $this->assertNotNull($row);
        $this->assertSame('Clínica Sur', $row->name);
        $this->assertNull($row->reply_to_email);
        $this->assertNull($row->frontend_url);
        $this->assertNull($row->reception_email);
        $this->assertSame('1', (string) $row->reception_email_enabled);
    }

    public function testContextIsRestoredEvenWhenCreationFails(): void
    {
        $before = TenantContext::current();

        try {
            (new TenantCreatorService())->create($this->request());
        } catch (RoleNotFoundException) {
        }

        $this->assertSame($before?->getId(), TenantContext::current()?->getId());
    }

    public function testTheSameEmailAndDniCanBeUsedInAnotherTenant(): void
    {
        $this->seedAdminRole();
        $service = new TenantCreatorService();

        $service->create($this->request('clinica-sur'));
        $service->create($this->request('clinica-norte'));

        $this->assertSame(2, $this->countRows('users'));
    }
}

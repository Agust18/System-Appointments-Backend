<?php

use App\Constants\Role;
use App\Entity\Platform\PlatformAdmin;
use App\Entity\Platform\PlatformAdminToken;
use App\Entity\Tenant\Tenant;
use App\Models\Platform\PlatformAdminModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Base de los tests HTTP del panel de plataforma: recorren el router y los filtros reales.
 *
 * Las excepciones de dominio (401 de credenciales, 409, 422) suben al test, porque el mapeo de su
 * código a HTTP vive en ApiExceptionHandler, que corta con exit: se verifica su código con
 * exceptionCodeOf(). Las respuestas de los Filters (401, 403, 429) sí llegan como TestResponse.
 *
 * El token se pasa por $_SERVER['HTTP_AUTHORIZATION'], que es de donde lo leen los filtros.
 */
abstract class PlatformEndpointTestCase extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    protected const ADMIN_EMAIL = 'root@plataforma.test';
    protected const ADMIN_PASSWORD = 'Secreta-12345';

    private mixed $previousEmailHost = null;

    protected function setUp(): void
    {
        parent::setUp();
        // El alta de un Tenant envía un email de bienvenida: sin EMAIL_HOST no abre ninguna conexión SMTP.
        $this->previousEmailHost = $_ENV['EMAIL_HOST'] ?? null;
        $_ENV['EMAIL_HOST'] = '';
        $this->resetServices();
        $this->clearSession();
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $this->setDemoContext();
    }

    protected function tearDown(): void
    {
        if ($this->previousEmailHost === null) {
            unset($_ENV['EMAIL_HOST']);
        } else {
            $_ENV['EMAIL_HOST'] = $this->previousEmailHost;
        }

        unset($_SERVER['HTTP_AUTHORIZATION']);
        $this->clearSession();

        // El mismo email/DNI en dos Tenants impide revertir la migración AddTenantIdToUsersAndPatients
        // (limitación del ADR 003): no dejar datos entre clases.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('DELETE FROM users WHERE tenant_id <> 1');
        $this->db->query('DELETE FROM tenants WHERE id <> 1');
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->setDemoContext();
        $this->resetServices();
        parent::tearDown();
    }

    /** El Tenant por defecto que fija tests/bootstrap.php; los requests con X-Tenant lo cambian. */
    protected function setDemoContext(): void
    {
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
    }

    protected function clearSession(): void
    {
        foreach (['platformAdminId', 'userId', 'roleId'] as $key) {
            session()->remove($key);
        }
    }

    /** service('response') es compartido: sin resetear, el status y los headers pasan al request siguiente. */
    protected function fresh(): static
    {
        $this->resetServices();
        $this->clearSession();

        return $this;
    }

    protected function seedAdminRole(): void
    {
        $this->db->table('roles')->insert(['id' => Role::ADMIN, 'name' => 'admin']);
    }

    /** Crea el platform_admin y devuelve un token de plataforma vigente. */
    protected function platformToken(bool $active = true): string
    {
        $model = new PlatformAdminModel();
        $admin = $model->insert(PlatformAdmin::create(self::ADMIN_EMAIL, 'Root', 'Admin', self::ADMIN_PASSWORD));
        $token = PlatformAdminToken::issueFor($admin->getId());
        $model->saveToken($token);

        if (!$active) {
            $this->db->query('UPDATE platform_admins SET is_active = 0 WHERE id = ?', [$admin->getId()]);
        }

        return $token->getToken();
    }

    protected function withToken(?string $token): static
    {
        if ($token === null) {
            unset($_SERVER['HTTP_AUTHORIZATION']);
        } else {
            $_SERVER['HTTP_AUTHORIZATION'] = $token;
        }

        return $this;
    }

    /** @return array<string, mixed> */
    protected function decode(TestResponse $result): array
    {
        return json_decode($result->getJSON(), true);
    }

    /** Código de la excepción que lanza el request (las de dominio suben al test), o null si no lanzó. */
    protected function exceptionCodeOf(callable $request): ?int
    {
        try {
            $request();
        } catch (\Throwable $e) {
            return $e->getCode();
        }

        return null;
    }

    /** @return array<string, mixed> */
    protected function tenantBody(array $overrides = []): array
    {
        return array_merge([
            'slug'           => 'clinica-sur',
            'name'           => 'Clínica Sur',
            'adminFirstName' => 'Ana',
            'adminLastName'  => 'Pérez',
            'adminEmail'     => 'ana@clinicasur.test',
            'adminDni'       => '30123456',
            'adminPassword'  => 'Temporal-123',
            'frontendUrl'    => 'https://clinica-sur.test.local',
        ], $overrides);
    }

    protected function rowsIn(string $table): int
    {
        return (int) $this->db->query("SELECT COUNT(*) AS c FROM {$table}")->getRow()->c;
    }
}

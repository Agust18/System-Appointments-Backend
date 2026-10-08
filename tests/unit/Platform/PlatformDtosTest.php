<?php

use App\Constants\Role;
use App\Constants\TenantStatus;
use App\Converter\Platform\PlatformAdminToPlatformAdminResponseConverter;
use App\Converter\Platform\TenantToTenantResponseConverter;
use App\Dto\Request\Platform\TenantRequest;
use App\Dto\Request\Platform\TenantUpdateRequest;
use App\Entity\Platform\PlatformAdmin;
use App\Entity\Tenant\Tenant;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class PlatformDtosTest extends CIUnitTestCase
{
    private function validTenantBody(array $override = []): array
    {
        return array_merge([
            'slug'           => 'clinica-sur',
            'name'           => 'Clínica Sur',
            'adminFirstName' => 'Ana',
            'adminLastName'  => 'Pérez',
            'adminEmail'     => 'ana@clinicasur.test',
            'adminDni'       => '30123456',
            'adminPassword'  => 'Temporal-123',
        ], $override);
    }

    private function runRules(array $rules, array $data): array
    {
        $validation = \Config\Services::validation(null, false);
        $validation->setRules($rules);
        $validation->run($data);

        return $validation->getErrors();
    }

    public function testValidTenantBodyPassesTheRules(): void
    {
        $this->assertSame([], $this->runRules(TenantRequest::rules(), $this->validTenantBody()));
    }

    /** @return array<string, array{string}> */
    public static function invalidSlugs(): array
    {
        return [
            'mayúsculas'            => ['Clinica'],
            'espacio'               => ['clinica sur'],
            'guion al inicio'       => ['-clinica'],
            'guion al final'        => ['clinica-'],
            'guion bajo'            => ['clinica_sur'],
            'punto (otro subnivel)' => ['a.b'],
            'más de 63 caracteres'  => [str_repeat('a', 64)],
        ];
    }

    #[DataProvider('invalidSlugs')]
    public function testInvalidSlugsAreRejected(string $slug): void
    {
        $errors = $this->runRules(TenantRequest::rules(), $this->validTenantBody(['slug' => $slug]));

        $this->assertArrayHasKey('slug', $errors);
    }

    public function testSingleCharacterSlugIsAccepted(): void
    {
        $this->assertSame([], $this->runRules(TenantRequest::rules(), $this->validTenantBody(['slug' => 'a'])));
    }

    public function testAdminPasswordAndDniAreRequired(): void
    {
        $errors = $this->runRules(
            TenantRequest::rules(),
            $this->validTenantBody(['adminPassword' => '', 'adminDni' => ''])
        );

        $this->assertArrayHasKey('adminPassword', $errors);
        $this->assertArrayHasKey('adminDni', $errors);
    }

    public function testUpdateOnlyAcceptsKnownStatuses(): void
    {
        $this->assertSame([], $this->runRules(TenantUpdateRequest::rules(), ['status' => 'suspended']));
        $this->assertArrayHasKey('status', $this->runRules(TenantUpdateRequest::rules(), ['status' => 'deleted']));
    }

    public function testAdminUserRequestHasTheAdminRole(): void
    {
        $request = new TenantRequest('clinica-sur', 'Clínica Sur', 'Ana', 'Pérez', 'ana@clinicasur.test', 30123456, 'Temporal-123');

        $user = $request->toAdminUserRequest();

        $this->assertSame(Role::ADMIN, $user->getRoleId());
        $this->assertTrue($user->getIsActive());
        $this->assertSame(30123456, $user->getDni());
        $this->assertSame('Temporal-123', $user->getPassword());
    }

    public function testTenantFromRequestStartsActive(): void
    {
        $tenant = Tenant::fromRequest(
            new TenantRequest('clinica-sur', 'Clínica Sur', 'Ana', 'Pérez', 'ana@clinicasur.test', 30123456, 'x')
        );

        $this->assertNull($tenant->getId());
        $this->assertSame('clinica-sur', $tenant->getSlug());
        $this->assertSame(TenantStatus::ACTIVE, $tenant->getStatus());
    }

    public function testModifyChangesNameAndStatusButNeverTheSlug(): void
    {
        $tenant = new Tenant(3, 'clinica-sur', 'Clínica Sur', TenantStatus::ACTIVE, null, null);

        $tenant->modify(new TenantUpdateRequest('Clínica Sur SA', TenantStatus::SUSPENDED));

        $this->assertSame('clinica-sur', $tenant->getSlug());
        $this->assertSame('Clínica Sur SA', $tenant->getName());
        $this->assertTrue($tenant->isSuspended());
    }

    public function testModifyWithNullsKeepsTheCurrentValues(): void
    {
        $tenant = new Tenant(3, 'clinica-sur', 'Clínica Sur', TenantStatus::SUSPENDED, null, null);

        $tenant->modify(new TenantUpdateRequest(null, null));

        $this->assertSame('Clínica Sur', $tenant->getName());
        $this->assertTrue($tenant->isSuspended());
    }

    public function testResponseConvertersDoNotLeakThePasswordHash(): void
    {
        $admin = new PlatformAdmin(5, 'root@plataforma.test', 'Root', 'Admin', password_hash('x', PASSWORD_BCRYPT), true, null, null);

        $json = json_encode((new PlatformAdminToPlatformAdminResponseConverter())->convert($admin));

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringContainsString('root@plataforma.test', $json);
    }

    public function testTenantResponseConverter(): void
    {
        $tenant = new Tenant(3, 'clinica-sur', 'Clínica Sur', TenantStatus::ACTIVE, new DateTime('2026-10-07 10:00:00'), null);

        $response = (new TenantToTenantResponseConverter())->convert($tenant);

        $this->assertSame(3, $response->id);
        $this->assertSame('2026-10-07 10:00:00', $response->createdAt);
        $this->assertNull($response->updatedAt);
    }
}

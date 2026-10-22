<?php

use App\Constants\Role;
use App\Dto\Request\Platform\TenantSettingsUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantSettingsModel;
use App\Services\Platform\Tenant\Validations\TenantSettingsUpdateValidatorService;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/PlatformEndpointTestCase.php';

/**
 * GET/PUT /platform/tenants/{id}/settings: la configuración la carga solo el panel. Cubre permisos
 * (solo un platform_admin activo), aislamiento (editar A no toca B), validación de frontendUrl y la
 * semántica del PUT (ausente = sin cambio, null = limpia).
 *
 * @internal
 */
final class TenantSettingsPlatformTest extends PlatformEndpointTestCase
{
    private string $token;
    private int $surId;
    private int $norteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->platformToken();
        $this->surId = $this->createTenant('clinica-sur', 'Sur Visible', 'sur');
        $this->norteId = $this->createTenant('clinica-norte', 'Norte Visible', 'norte');
    }

    private function createTenant(string $slug, string $visibleName, string $prefix): int
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => $visibleName, 'status' => 'active']);
        $id = $this->db->insertID();
        $this->db->table('tenant_settings')->insert([
            'tenant_id'               => $id,
            'name'                    => $visibleName,
            'reply_to_email'          => "respuestas@{$prefix}.test",
            'frontend_url'            => "https://{$prefix}.tusturnos.test",
            'reception_email'         => "recepcion@{$prefix}.test",
            'reception_email_enabled' => 1,
        ]);

        return $id;
    }

    private function editSettings(int $id, array $body, ?string $token = null)
    {
        return $this->withToken($token ?? $this->token)->fresh()->withBodyFormat('json')->put("platform/tenants/{$id}/settings", $body);
    }

    private function readSettings(int $id, ?string $token = null)
    {
        return $this->withToken($token ?? $this->token)->fresh()->get("platform/tenants/{$id}/settings");
    }

    /** @return array<string, mixed> */
    private function row(int $tenantId): array
    {
        return $this->db->query('SELECT * FROM tenant_settings WHERE tenant_id = ?', [$tenantId])->getRowArray();
    }

    /** @return array<string, string> errores por campo de la ValidationException que lanza el request */
    private function errorsOf(callable $request): array
    {
        try {
            $request();
        } catch (ValidationException $e) {
            return $e->getErrors();
        }

        $this->fail('Debió fallar la validación.');
    }

    // ------------------------------- permisos

    public function testWithoutTokenBothRoutesAnswer401(): void
    {
        $this->withToken(null)->fresh()->get("platform/tenants/{$this->surId}/settings")->assertStatus(401);
        $this->withToken(null)->fresh()->withBodyFormat('json')->put("platform/tenants/{$this->surId}/settings", ['name' => 'X!'])->assertStatus(401);

        $this->assertSame('Sur Visible', $this->row($this->surId)['name']);
    }

    public function testATenantAdminTokenCannotReadNorEditSettings(): void
    {
        $this->seedAdminRole();
        $this->db->query(
            "INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, is_active, is_verified, role_id, token, token_expiration_date)
             VALUES (?, 'Ana', 'Admin', 'ana@sur.test', 'x', 30123456, 1, 1, ?, 'token-admin-sur', '2099-01-01 00:00:00')",
            [$this->surId, Role::ADMIN]
        );

        $this->readSettings($this->surId, 'token-admin-sur')->assertStatus(401);
        $this->editSettings($this->surId, ['name' => 'Hackeado'], 'token-admin-sur')->assertStatus(401);

        $this->assertSame('Sur Visible', $this->row($this->surId)['name']);
    }

    public function testADeactivatedPlatformAdminAnswers403AndWritesNothing(): void
    {
        $this->db->query('UPDATE platform_admins SET is_active = 0');

        $this->readSettings($this->surId)->assertStatus(403);
        $this->editSettings($this->surId, ['name' => 'Hackeado'])->assertStatus(403);

        $this->assertSame('Sur Visible', $this->row($this->surId)['name']);
    }

    // ------------------------------- lectura y aislamiento

    public function testGetReturnsTheFullSettingsOfThatTenantOnly(): void
    {
        $sur = $this->readSettings($this->surId);
        $norte = $this->readSettings($this->norteId);

        $sur->assertStatus(200);
        $this->assertSame([
            'tenantId'              => $this->surId,
            'name'                  => 'Sur Visible',
            'replyToEmail'          => 'respuestas@sur.test',
            'frontendUrl'           => 'https://sur.tusturnos.test',
            'receptionEmail'        => 'recepcion@sur.test',
            'receptionEmailEnabled' => true,
            'contactPhone'          => null,
            'contactWhatsapp'       => null,
            'contactEmail'          => null,
            'address'               => null,
            'openingHours'          => null,
            'landingEnabled'        => true,
            'heroTitle'             => null,
            'heroSubtitle'          => null,
        ], array_diff_key($this->decode($sur), ['createdAt' => 1, 'updatedAt' => 1]));
        $this->assertSame($this->norteId, $this->decode($norte)['tenantId']);
        $this->assertSame('recepcion@norte.test', $this->decode($norte)['receptionEmail']);
    }

    public function testEditingTenantANeverTouchesTenantBNorDemo(): void
    {
        $beforeNorte = $this->row($this->norteId);
        $beforeDemo = $this->row(1);

        $result = $this->editSettings($this->surId, [
            'name'                  => 'Sur Nuevo',
            'replyToEmail'          => null,
            'frontendUrl'           => 'https://nuevo.sur.test',
            'receptionEmail'        => 'otra@sur.test',
            'receptionEmailEnabled' => false,
        ]);

        $result->assertStatus(200);
        $this->assertSame('Sur Nuevo', $this->row($this->surId)['name']);
        $this->assertSame($beforeNorte, $this->row($this->norteId));
        $this->assertSame($beforeDemo, $this->row(1));
    }

    public function testATenantIdInTheBodyIsIgnored(): void
    {
        $beforeNorte = $this->row($this->norteId);

        $result = $this->editSettings($this->surId, ['tenantId' => $this->norteId, 'name' => 'Sur Nuevo']);

        $result->assertStatus(200);
        $this->assertSame($this->surId, $this->decode($result)['tenantId']);
        $this->assertSame('Sur Nuevo', $this->row($this->surId)['name']);
        $this->assertSame($beforeNorte, $this->row($this->norteId));
    }

    public function testTheModelUpdateOnlyAffectsTheRowOfTheEntityTenant(): void
    {
        $model = new TenantSettingsModel();
        $beforeNorte = $this->row($this->norteId);

        $settings = $model->findByTenantId($this->surId);
        $settings->modify(TenantSettingsUpdateRequest::fromArray(['name' => 'Cambiado']));
        $model->update($settings);

        $this->assertSame('Cambiado', $this->row($this->surId)['name']);
        $this->assertSame($beforeNorte, $this->row($this->norteId));
    }

    public function testAnUnknownTenantIs404AndWritesNothing(): void
    {
        $rows = $this->rowsIn('tenant_settings');

        $this->assertSame(404, $this->exceptionCodeOf(fn () => $this->readSettings(9999)));
        $this->assertSame(404, $this->exceptionCodeOf(fn () => $this->editSettings(9999, ['name' => 'Fantasma'])));

        $this->assertSame($rows, $this->rowsIn('tenant_settings'));
    }

    public function testASuspendedTenantCanStillBeEditedFromThePanel(): void
    {
        $this->db->query("UPDATE tenants SET status = 'suspended' WHERE id = ?", [$this->surId]);

        $this->editSettings($this->surId, ['name' => 'Sur Suspendido'])->assertStatus(200);

        $this->assertSame('Sur Suspendido', $this->row($this->surId)['name']);
    }

    public function testThePublicConfigReflectsTheEditButStillHidesInternalData(): void
    {
        $this->editSettings($this->surId, ['name' => 'Sur Nuevo', 'receptionEmail' => 'secreto@sur.test'])->assertStatus(200);

        $public = $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => 'clinica-sur'])->get('tenant/config');

        $public->assertStatus(200);
        $this->assertSame('clinica-sur', $this->decode($public)['slug']);
        $this->assertSame('Sur Nuevo', $this->decode($public)['name']);
        $this->assertStringNotContainsString('secreto@sur.test', $public->getJSON());
    }

    // ------------------------------- contacto público

    public function testContactIsSavedNormalizedAndPublishedWithoutInternalEmails(): void
    {
        $this->editSettings($this->surId, [
            'contactPhone'    => '+54 (11) 4000-1234',
            'contactWhatsapp' => '+54 9 11 5555-1234',
            'contactEmail'    => 'info@sur.test',
            'address'         => 'Av. Siempreviva 742',
            'openingHours'    => 'Lun a Vie 8 a 18',
            'landingEnabled'  => false,
        ])->assertStatus(200);

        $row = $this->row($this->surId);
        $this->assertSame('5491155551234', $row['contact_whatsapp']);
        $this->assertSame('0', (string) $row['landing_enabled']);

        $public = $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => 'clinica-sur'])->get('tenant/config');
        $json = $this->decode($public);
        $this->assertFalse($json['landingEnabled']);
        $this->assertSame('info@sur.test', $json['contact']['email']);
        $this->assertSame('5491155551234', $json['contact']['whatsapp']);
        foreach (['recepcion@sur.test', 'respuestas@sur.test', 'sur.tusturnos.test'] as $secret) {
            $this->assertStringNotContainsString($secret, $public->getJSON());
        }
    }

    public function testContactFieldsKeepWhenAbsentAndClearWithNull(): void
    {
        $this->editSettings($this->surId, ['contactPhone' => '4000-1234', 'address' => 'Calle 1'])->assertStatus(200);
        $this->editSettings($this->surId, ['name' => 'Otro'])->assertStatus(200);
        $this->assertSame('4000-1234', $this->row($this->surId)['contact_phone']);

        $this->editSettings($this->surId, ['contactPhone' => null, 'address' => ''])->assertStatus(200);
        $row = $this->row($this->surId);
        $this->assertNull($row['contact_phone']);
        $this->assertNull($row['address']);
    }

    public function testHeroTextsArePublishedKeepWhenAbsentAndClearWithNull(): void
    {
        $this->editSettings($this->surId, ['heroTitle' => '  Tu kinesiólogo de confianza ', 'heroSubtitle' => 'Turnos online'])->assertStatus(200);

        $public = $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => 'clinica-sur'])->get('tenant/config');
        $this->assertSame('Tu kinesiólogo de confianza', $this->decode($public)['heroTitle']);
        $this->assertSame('Turnos online', $this->decode($public)['heroSubtitle']);

        $this->editSettings($this->surId, ['name' => 'Otro'])->assertStatus(200);
        $this->assertSame('Turnos online', $this->row($this->surId)['hero_subtitle']);

        $this->editSettings($this->surId, ['heroTitle' => '', 'heroSubtitle' => null])->assertStatus(200);
        $row = $this->row($this->surId);
        $this->assertNull($row['hero_title']);
        $this->assertNull($row['hero_subtitle']);
    }

    public function testTooLongHeroTitleIsRejected(): void
    {
        $this->editSettings($this->surId, ['heroTitle' => str_repeat('a', 256)])->assertStatus(422);
        $this->assertNull($this->row($this->surId)['hero_title']);
    }

    public function testInvalidContactIsRejectedAndLeavesTheRowIntact(): void
    {
        $before = $this->row($this->surId);

        $this->assertArrayHasKey('contactEmail', $this->errorsOf(fn () => $this->editSettings($this->surId, ['contactEmail' => 'no-es-email'])));
        $this->assertArrayHasKey('contactPhone', $this->errorsOf(fn () => $this->editSettings($this->surId, ['contactPhone' => '<script>alert(1)</script>'])));
        $this->assertArrayHasKey('contactWhatsapp', $this->errorsOf(fn () => $this->editSettings($this->surId, ['contactWhatsapp' => '123'])));
        $this->assertArrayHasKey('landingEnabled', $this->errorsOf(fn () => $this->editSettings($this->surId, ['landingEnabled' => null, 'name' => 'Valido'])));
        $this->assertArrayHasKey('openingHours', $this->errorsOf(fn () => $this->editSettings($this->surId, ['openingHours' => str_repeat('a', 501)])));

        $this->assertSame($before, $this->row($this->surId));
    }

    public function testEditingContactOfTenantANeverTouchesTenantB(): void
    {
        $beforeNorte = $this->row($this->norteId);

        $this->editSettings($this->surId, ['contactPhone' => '4000-1234', 'landingEnabled' => false])->assertStatus(200);

        $this->assertSame($beforeNorte, $this->row($this->norteId));
    }

    // ------------------------------- semántica del PUT

    public function testAbsentFieldsAreKeptAndNullOrEmptyClear(): void
    {
        $this->editSettings($this->surId, ['name' => 'Solo Nombre'])->assertStatus(200);
        $row = $this->row($this->surId);
        $this->assertSame('respuestas@sur.test', $row['reply_to_email']);
        $this->assertSame('https://sur.tusturnos.test', $row['frontend_url']);
        $this->assertSame('recepcion@sur.test', $row['reception_email']);

        $this->editSettings($this->surId, ['replyToEmail' => null, 'frontendUrl' => '', 'receptionEmail' => '   '])->assertStatus(200);
        $row = $this->row($this->surId);
        $this->assertNull($row['reply_to_email']);
        $this->assertNull($row['frontend_url']);
        $this->assertNull($row['reception_email']);
        $this->assertSame('Solo Nombre', $row['name']);
    }

    public function testAnEmptyOrUnknownBodyIs422(): void
    {
        $this->assertArrayHasKey('body', $this->errorsOf(fn () => $this->editSettings($this->surId, [])));
        $this->assertArrayHasKey('body', $this->errorsOf(fn () => $this->editSettings($this->surId, ['color' => 'rojo'])));
    }

    public function testNameAndTheEnabledFlagCannotBeCleared(): void
    {
        $before = $this->row($this->surId);

        $this->assertArrayHasKey('name', $this->errorsOf(fn () => $this->editSettings($this->surId, ['name' => null])));
        $this->assertArrayHasKey('name', $this->errorsOf(fn () => $this->editSettings($this->surId, ['name' => '  '])));
        $this->assertArrayHasKey('receptionEmailEnabled', $this->errorsOf(fn () => $this->editSettings($this->surId, ['receptionEmailEnabled' => null, 'name' => 'Valido'])));
        $this->assertArrayHasKey('receptionEmailEnabled', $this->errorsOf(fn () => $this->editSettings($this->surId, ['receptionEmailEnabled' => 'yes'])));

        $this->assertSame($before, $this->row($this->surId));
    }

    public function testInvalidEmailsAreRejected(): void
    {
        foreach (['replyToEmail', 'receptionEmail'] as $field) {
            foreach (['no-es-un-email', "a@b.co\r\nBcc: x@y.co", str_repeat('a', 250) . '@b.co'] as $bad) {
                $this->assertArrayHasKey($field, $this->errorsOf(fn () => $this->editSettings($this->surId, [$field => $bad])), "{$field}: {$bad}");
            }
        }
    }

    // ------------------------------- claves de color obsoletas

    public function testLegacyColorKeysAreIgnoredAndTheRestIsSaved(): void
    {
        $result = $this->editSettings($this->surId, ['name' => 'Sur Nuevo', 'primaryColor' => '#1A73E8', 'secondaryColor' => 'no-es-un-color']);

        $result->assertStatus(200);
        $this->assertSame('Sur Nuevo', $this->row($this->surId)['name']);
        $this->assertArrayNotHasKey('primaryColor', $this->decode($result));
        $this->assertArrayNotHasKey('secondaryColor', $this->decode($result));
    }

    public function testABodyWithOnlyLegacyColorKeysIs422(): void
    {
        $errors = $this->errorsOf(fn () => $this->editSettings($this->surId, ['primaryColor' => '#1A73E8']));

        $this->assertArrayHasKey('body', $errors);
    }

    // ------------------------------- frontendUrl

    /** @return array<string, array{mixed}> */
    public static function invalidUrls(): array
    {
        return [
            'javascript'      => ['javascript:alert(1)'],
            'ftp'             => ['ftp://sur.test'],
            'sin esquema'     => ['sur.test'],
            'query'           => ['https://sur.test?x=1'],
            'fragmento'       => ['https://sur.test#x'],
            'userinfo'        => ['https://usuario@sur.test'],
            'userinfo y pass' => ['https://usuario:clave@sur.test'],
            'arroba de phishing' => ['https://sur.test@evil.test'],
            'con ruta'        => ['https://sur.test/panel'],
            'barra invertida' => ['https://sur.test\\@evil.test'],
            'espacio'         => ['https://sur test.com'],
            'puerto absurdo'  => ['https://sur.test:99999'],
            'muy larga'       => ['https://' . str_repeat('a', 250) . '.test'],
            'no es string'    => [['https://sur.test']],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function testAnInvalidFrontendUrlIs422AndLeavesTheRowIntact(mixed $url): void
    {
        $before = $this->row($this->surId);

        $errors = $this->errorsOf(fn () => $this->editSettings($this->surId, ['name' => 'No debe guardarse', 'frontendUrl' => $url]));

        $this->assertArrayHasKey('frontendUrl', $errors);
        $this->assertSame($before, $this->row($this->surId));
    }

    public function testAValidFrontendUrlIsSavedNormalized(): void
    {
        $this->editSettings($this->surId, ['frontendUrl' => 'HTTPS://Sur.Tusturnos.com/'])->assertStatus(200);
        $this->assertSame('https://sur.tusturnos.com', $this->row($this->surId)['frontend_url']);

        $this->editSettings($this->surId, ['frontendUrl' => ' https://sur.test:8443 '])->assertStatus(200);
        $this->assertSame('https://sur.test:8443', $this->row($this->surId)['frontend_url']);
    }

    public function testHttpIsOnlyAcceptedOutsideProduction(): void
    {
        $request = TenantSettingsUpdateRequest::fromArray(['frontendUrl' => 'http://localhost:5173']);

        (new TenantSettingsUpdateValidatorService(true))->validate($request);

        $this->expectException(ValidationException::class);
        (new TenantSettingsUpdateValidatorService(false))->validate($request);
    }

    public function testHttpsIsAcceptedInProduction(): void
    {
        $request = TenantSettingsUpdateRequest::fromArray(['frontendUrl' => 'https://sur.tusturnos.com']);

        (new TenantSettingsUpdateValidatorService(false))->validate($request);

        $this->addToAssertionCount(1);
    }
}

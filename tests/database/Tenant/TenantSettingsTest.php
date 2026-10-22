<?php

use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotResolvedException;
use App\Models\Tenant\TenantSettingsModel;
use App\Tenancy\TenantContext;

require_once __DIR__ . '/../Platform/PlatformEndpointTestCase.php';
require_once APPPATH . 'Database/Migrations/2026-10-07-110000_CreateTenantSettingsTable.php';
require_once APPPATH . 'Database/Migrations/2026-10-16-100000_AddContactToTenantSettings.php';

/**
 * Configuración por Tenant: aislamiento entre Tenants, datos que NO se publican en
 * GET /tenant/config, suspensión y backfill de la migración.
 *
 * @internal
 */
final class TenantSettingsTest extends PlatformEndpointTestCase
{
    private int $otherId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'clinica-sur', 'name' => 'Clínica Sur', 'status' => 'active']);
        $this->otherId = $this->db->insertID();
        $this->db->table('tenant_settings')->insert([
            'tenant_id'               => $this->otherId,
            'name'                    => 'Sur Visible',
            'reply_to_email'          => 'respuestas@sur.test',
            'frontend_url'            => 'https://sur.tusturnos.test',
            'reception_email'         => 'recepcion@sur.test',
            'reception_email_enabled' => 1,
        ]);
    }

    private function asTenant(string $slug)
    {
        return $this->withToken(null)->fresh()->withHeaders(['X-Tenant' => $slug]);
    }

    public function testEachTenantReadsItsOwnSettings(): void
    {
        $demo = $this->asTenant('demo')->get('tenant/config');
        $sur = $this->asTenant('clinica-sur')->get('tenant/config');

        $demo->assertStatus(200);
        $sur->assertStatus(200);
        // assertSame sobre el array completo: también garantiza que no vuelvan claves de color.
        $emptyContact = ['phone' => null, 'whatsapp' => null, 'email' => null, 'address' => null, 'openingHours' => null];
        $this->assertSame(['slug' => 'demo', 'name' => 'Demo', 'landingEnabled' => true, 'contact' => $emptyContact, 'heroTitle' => null, 'heroSubtitle' => null], $this->decode($demo));
        $this->assertSame(['slug' => 'clinica-sur', 'name' => 'Sur Visible', 'landingEnabled' => true, 'contact' => $emptyContact, 'heroTitle' => null, 'heroSubtitle' => null], $this->decode($sur));
    }

    public function testTheModelNeverReturnsTheRowOfAnotherTenant(): void
    {
        $model = new TenantSettingsModel();

        $this->assertSame('Demo', $model->find()->getName());

        TenantContext::set(new Tenant($this->otherId, 'clinica-sur', 'Clínica Sur', 'active', null, null));
        $this->assertSame('Sur Visible', $model->find()->getName());
        $this->assertSame($this->otherId, $model->find()->getTenantId());
    }

    public function testTheModelFailsWithoutTenantContext(): void
    {
        TenantContext::clear();

        $this->expectException(TenantNotResolvedException::class);
        (new TenantSettingsModel())->find();
    }

    public function testThePublicConfigDoesNotLeakInternalSettings(): void
    {
        $result = $this->asTenant('clinica-sur')->get('tenant/config');

        $this->assertSame(['slug', 'name', 'landingEnabled', 'contact', 'heroTitle', 'heroSubtitle'], array_keys($this->decode($result)));
        $this->assertSame(['phone', 'whatsapp', 'email', 'address', 'openingHours'], array_keys($this->decode($result)['contact']));
        foreach (['recepcion@sur.test', 'respuestas@sur.test', 'sur.tusturnos.test'] as $secret) {
            $this->assertStringNotContainsString($secret, $result->getJSON());
        }
    }

    public function testASuspendedTenantAnswers403(): void
    {
        $this->db->query("UPDATE tenants SET status = 'suspended' WHERE id = ?", [$this->otherId]);

        $this->asTenant('clinica-sur')->get('tenant/config')->assertStatus(403);
        $this->asTenant('demo')->get('tenant/config')->assertStatus(200);
    }

    public function testMigrationBackfillsEveryExistingTenant(): void
    {
        // Tenants que existían antes de la migración: sin tabla de settings.
        $this->db->query('DROP TABLE tenant_settings');
        $this->db->table('tenants')->insert(['slug' => 'clinica-norte', 'name' => 'Clínica Norte', 'status' => 'suspended']);

        try {
            (new \App\Database\Migrations\CreateTenantSettingsTable())->up();
            (new \App\Database\Migrations\AddContactToTenantSettings())->up();

            $rows = $this->db->query('SELECT t.slug, s.name, s.reception_email_enabled FROM tenants t LEFT JOIN tenant_settings s ON s.tenant_id = t.id ORDER BY t.id')->getResultArray();
            $this->assertCount(3, $rows);
            foreach ($rows as $row) {
                $this->assertNotNull($row['name'], "{$row['slug']} quedó sin fila de settings");
                $this->assertSame('1', (string) $row['reception_email_enabled']);
            }
        } finally {
            // Deja el esquema como lo espera el resto de la suite.
            (new \App\Database\Migrations\CreateTenantSettingsTable())->up();
            (new \App\Database\Migrations\AddContactToTenantSettings())->up();
        }
    }
}

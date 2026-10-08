<?php

namespace Tests\Support\Database;

/**
 * Los emails de verificación y reset no tienen respaldo en el .env: sin `frontend_url` en
 * `tenant_settings` el envío falla con 500. Los tests que llegan a ese envío siembran la
 * configuración con este helper (requiere $this->db, es decir, un trait de base de datos).
 */
trait SeedsTenantEmailSettings
{
    protected function seedTenantEmailSettings(int $tenantId = 1, array $values = []): void
    {
        $values = $values + [
            'frontend_url'            => 'https://demo.test.local',
            'reply_to_email'          => null,
            'reception_email'         => null,
            'reception_email_enabled' => 1,
        ];

        $this->db->table('tenant_settings')->where('tenant_id', $tenantId)->update($values);
    }
}

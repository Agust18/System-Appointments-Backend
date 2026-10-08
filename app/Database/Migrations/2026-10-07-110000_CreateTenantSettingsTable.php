<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Multitenant, Paso 4: configuración por Tenant (1:1 con `tenants`, PK = tenant_id).
 *
 * Backfill: cada Tenant existente recibe su fila (name = nombre del Tenant, el resto NULL), porque
 * un Tenant sin fila de settings respondería 500 en GET /tenant/config.
 *
 * `tenant_id` no lleva DEFAULT. La FK es ON DELETE CASCADE solo acá: las FK de las tablas con datos
 * siguen impidiendo borrar un Tenant que ya los tiene.
 */
class CreateTenantSettingsTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tenant_settings` (
              `tenant_id` int UNSIGNED NOT NULL,
              `name` varchar(150) NOT NULL,
              `reply_to_email` varchar(255) DEFAULT NULL,
              `frontend_url` varchar(255) DEFAULT NULL,
              `reception_email` varchar(255) DEFAULT NULL,
              `reception_email_enabled` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`tenant_id`),
              CONSTRAINT `fk_tenant_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        $this->db->query("
            INSERT INTO tenant_settings (tenant_id, name)
            SELECT t.id, t.name FROM tenants t
            WHERE NOT EXISTS (SELECT 1 FROM tenant_settings s WHERE s.tenant_id = t.id)
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `tenant_settings`");
    }
}

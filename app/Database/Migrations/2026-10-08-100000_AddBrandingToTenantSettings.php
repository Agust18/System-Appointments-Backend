<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Multitenant, Paso 4 (branding): colores por Tenant. NULL = sin color elegido (el frontend usa su
 * tema por defecto), por eso no hay backfill. Formato `#RRGGBB` en mayúsculas, validado en la app.
 */
class AddBrandingToTenantSettings extends Migration
{
    public function up()
    {
        if (! $this->columnExists('primary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` ADD COLUMN `primary_color` char(7) DEFAULT NULL AFTER `reception_email_enabled`");
        }
        if (! $this->columnExists('secondary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` ADD COLUMN `secondary_color` char(7) DEFAULT NULL AFTER `primary_color`");
        }
    }

    public function down()
    {
        if ($this->columnExists('secondary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` DROP COLUMN `secondary_color`");
        }
        if ($this->columnExists('primary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` DROP COLUMN `primary_color`");
        }
    }

    private function columnExists(string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `tenant_settings` LIKE ?", [$column])->getNumRows() > 0;
    }
}

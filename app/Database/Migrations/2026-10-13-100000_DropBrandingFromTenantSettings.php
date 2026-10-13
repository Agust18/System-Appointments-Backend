<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Quita el branding de colores por Tenant (el frontend dejó de usarlo). Destructiva: se pierden los
 * valores cargados. `down()` recrea las columnas vacías (NULL), no los datos.
 */
class DropBrandingFromTenantSettings extends Migration
{
    public function up()
    {
        if ($this->columnExists('secondary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` DROP COLUMN `secondary_color`");
        }
        if ($this->columnExists('primary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` DROP COLUMN `primary_color`");
        }
    }

    public function down()
    {
        if (! $this->columnExists('primary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` ADD COLUMN `primary_color` char(7) DEFAULT NULL AFTER `reception_email_enabled`");
        }
        if (! $this->columnExists('secondary_color')) {
            $this->db->query("ALTER TABLE `tenant_settings` ADD COLUMN `secondary_color` char(7) DEFAULT NULL AFTER `primary_color`");
        }
    }

    private function columnExists(string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `tenant_settings` LIKE ?", [$column])->getNumRows() > 0;
    }
}

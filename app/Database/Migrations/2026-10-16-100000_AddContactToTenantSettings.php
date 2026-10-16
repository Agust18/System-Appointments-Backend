<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Contacto público por Tenant (primera pasada) + casilla `landing_enabled`. NULL = no cargado, por
 * eso no hay backfill; `landing_enabled` nace en 1 para que los Tenants existentes conserven la landing.
 */
class AddContactToTenantSettings extends Migration
{
    private const COLUMNS = [
        'contact_phone'   => "varchar(50) DEFAULT NULL AFTER `reception_email_enabled`",
        'contact_whatsapp' => "varchar(20) DEFAULT NULL AFTER `contact_phone`",
        'contact_email'   => "varchar(255) DEFAULT NULL AFTER `contact_whatsapp`",
        'address'         => "varchar(255) DEFAULT NULL AFTER `contact_email`",
        'opening_hours'   => "varchar(500) DEFAULT NULL AFTER `address`",
        'landing_enabled' => "tinyint(1) NOT NULL DEFAULT 1 AFTER `opening_hours`",
    ];

    public function up()
    {
        foreach (self::COLUMNS as $column => $definition) {
            if (! $this->columnExists($column)) {
                $this->db->query("ALTER TABLE `tenant_settings` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    public function down()
    {
        foreach (array_reverse(array_keys(self::COLUMNS)) as $column) {
            if ($this->columnExists($column)) {
                $this->db->query("ALTER TABLE `tenant_settings` DROP COLUMN `{$column}`");
            }
        }
    }

    private function columnExists(string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `tenant_settings` LIKE ?", [$column])->getNumRows() > 0;
    }
}

<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Textos del hero de la landing por Tenant. NULL = se usa el texto por defecto del frontend,
 * por eso no hay backfill.
 */
class AddHeroToTenantSettings extends Migration
{
    private const COLUMNS = [
        'hero_title'    => "varchar(255) DEFAULT NULL AFTER `landing_enabled`",
        'hero_subtitle' => "varchar(255) DEFAULT NULL AFTER `hero_title`",
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

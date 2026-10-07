<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Multitenant, Paso 2 (grupo 2): specialties, services, medicals, medical_schedules y
 * medical_service pasan a pertenecer a un Tenant.
 *
 * - Las filas existentes quedan en el Tenant 1 ('demo', sembrado por AddTenantIdToUsersAndPatients).
 * - specialties.name deja de ser único global: pasa a UNIQUE(tenant_id, name).
 *   medical_service conserva UNIQUE(medical_id, service_id): los ids ya son únicos en toda la base.
 *
 * tenant_id lleva DEFAULT 1 TRANSITORIO (igual que en el grupo 1); la última migración del
 * Paso 2 lo elimina (ver bitacora/pendiente/multitenant-plan.md).
 */
class AddTenantIdToCatalogAndMedicalTables extends Migration
{
    private const TABLES = ['specialties', 'services', 'medicals', 'medical_schedules', 'medical_service'];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
        }

        $this->db->query("ALTER TABLE specialties ADD UNIQUE KEY uq_specialties_tenant_name (tenant_id, name)");
        $this->db->query("ALTER TABLE specialties DROP INDEX name");

        foreach (self::TABLES as $table) {
            $this->db->query(
                "ALTER TABLE {$table} ADD CONSTRAINT fk_{$table}_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)"
            );
        }
    }

    /**
     * No se puede revertir si dos Tenants tienen una especialidad con el mismo nombre (el único
     * global no podría volver a crearse). El pre-check corre antes de escribir nada: falla limpio
     * y la base queda intacta. Los índices y FK se chequean antes de tocarlos para poder
     * reintentar un down() interrumpido.
     */
    public function down()
    {
        $this->assertNoDuplicateSpecialtyNamesAcrossTenants();

        foreach (array_reverse(self::TABLES) as $table) {
            if ($this->constraintExists($table, "fk_{$table}_tenant")) {
                $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY fk_{$table}_tenant");
            }
        }

        if (!$this->indexExists('specialties', 'name')) {
            $this->db->query("ALTER TABLE specialties ADD UNIQUE KEY name (name)");
        }
        if ($this->indexExists('specialties', 'uq_specialties_tenant_name')) {
            $this->db->query("ALTER TABLE specialties DROP INDEX uq_specialties_tenant_name");
        }

        foreach (array_reverse(self::TABLES) as $table) {
            if ($this->columnExists($table, 'tenant_id')) {
                $this->db->query("ALTER TABLE {$table} DROP COLUMN tenant_id");
            }
        }
    }

    private function assertNoDuplicateSpecialtyNamesAcrossTenants(): void
    {
        if (!$this->columnExists('specialties', 'tenant_id')) {
            return;
        }

        $rows = $this->db->query(
            "SELECT name, GROUP_CONCAT(DISTINCT tenant_id ORDER BY tenant_id) AS tenants
             FROM specialties GROUP BY name HAVING COUNT(*) > 1"
        )->getResultArray();

        if ($rows === []) {
            return;
        }

        $duplicates = [];

        foreach ($rows as $row) {
            $duplicates[] = sprintf('"%s" (tenants: %s)', $row['name'], str_replace(',', ', ', $row['tenants']));
        }

        throw new RuntimeException(
            'No se puede revertir: existen especialidades con el mismo nombre entre tenants. '
            . 'Unificá o eliminá esas especialidades y volvé a revertir. Duplicados: ' . implode('; ', $duplicates) . '.'
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])->getResultArray() !== [];
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])->getResultArray() !== [];
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return $this->db->query(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraint]
        )->getResultArray() !== [];
    }
}

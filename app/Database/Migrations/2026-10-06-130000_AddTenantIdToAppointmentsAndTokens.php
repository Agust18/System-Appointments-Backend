<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Multitenant, Paso 2 (grupo 3): appointments y user_verification_tokens pasan a pertenecer a
 * un Tenant.
 *
 * - Las filas existentes quedan en el Tenant 1 ('demo', sembrado por AddTenantIdToUsersAndPatients).
 * - No cambia ningún único: appointments no tiene, y user_verification_tokens.token sigue siendo
 *   UNIQUE global (token aleatorio). Por eso el down() no necesita pre-check de duplicados.
 * - idx_appointments_tenant_fecha sirve a las métricas (filtran por Tenant y rango de fechas); la
 *   FK de tenant_id lo reutiliza. hasOverlap y la disponibilidad siguen usando
 *   idx_medical_fecha_status (medical_id es único en toda la base).
 *
 * tenant_id lleva DEFAULT 1 TRANSITORIO (igual que en los grupos 1 y 2); la migración
 * DropTenantIdDefaults, que cierra el Paso 2, lo elimina (ver bitacora/pendiente/multitenant-plan.md).
 */
class AddTenantIdToAppointmentsAndTokens extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE appointments ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
        $this->db->query("ALTER TABLE appointments ADD KEY idx_appointments_tenant_fecha (tenant_id, fecha)");
        $this->db->query(
            "ALTER TABLE appointments ADD CONSTRAINT fk_appointments_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)"
        );

        $this->db->query("ALTER TABLE user_verification_tokens ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
        $this->db->query(
            "ALTER TABLE user_verification_tokens ADD CONSTRAINT fk_user_verification_tokens_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)"
        );
    }

    /**
     * Los índices, FK y columnas se chequean antes de tocarlos para poder reintentar un down()
     * interrumpido. La FK se elimina antes que el índice que la respalda.
     */
    public function down()
    {
        if ($this->constraintExists('user_verification_tokens', 'fk_user_verification_tokens_tenant')) {
            $this->db->query("ALTER TABLE user_verification_tokens DROP FOREIGN KEY fk_user_verification_tokens_tenant");
        }
        if ($this->columnExists('user_verification_tokens', 'tenant_id')) {
            $this->db->query("ALTER TABLE user_verification_tokens DROP COLUMN tenant_id");
        }

        if ($this->constraintExists('appointments', 'fk_appointments_tenant')) {
            $this->db->query("ALTER TABLE appointments DROP FOREIGN KEY fk_appointments_tenant");
        }
        if ($this->indexExists('appointments', 'idx_appointments_tenant_fecha')) {
            $this->db->query("ALTER TABLE appointments DROP INDEX idx_appointments_tenant_fecha");
        }
        if ($this->columnExists('appointments', 'tenant_id')) {
            $this->db->query("ALTER TABLE appointments DROP COLUMN tenant_id");
        }
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

<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Multitenant, Paso 2 (grupo 1): users y patients pasan a pertenecer a un Tenant.
 *
 * - Crea el Tenant 1 ('demo') y le asigna todas las filas existentes (los datos son ficticios).
 * - users.email y users.dni dejan de ser únicos globales: pasan a UNIQUE(tenant_id, ...).
 *   patients.user_id sigue UNIQUE (un user, un paciente).
 *
 * tenant_id lleva DEFAULT 1 TRANSITORIO para que el resto del código y de los tests sigan
 * funcionando mientras se migran los demás grupos. La última migración del Paso 2 lo elimina
 * (ver bitacora/pendiente/multitenant-plan.md).
 */
class AddTenantIdToUsersAndPatients extends Migration
{
    public function up()
    {
        $this->db->query("
            INSERT INTO tenants (id, slug, name, status) VALUES (1, 'demo', 'Demo', 'active')
            ON DUPLICATE KEY UPDATE id = id
        ");

        $this->db->query("ALTER TABLE users ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
        $this->db->query("ALTER TABLE users ADD UNIQUE KEY uq_users_tenant_email (tenant_id, email)");
        $this->db->query("ALTER TABLE users ADD UNIQUE KEY uq_users_tenant_dni (tenant_id, dni)");
        $this->db->query("ALTER TABLE users DROP INDEX email");
        $this->db->query("ALTER TABLE users DROP INDEX uq_users_dni");
        $this->db->query("ALTER TABLE users ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)");

        $this->db->query("ALTER TABLE patients ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
        $this->db->query("ALTER TABLE patients ADD CONSTRAINT fk_patients_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)");
    }

    /**
     * No se puede revertir si dos usuarios comparten email o DNI (con distinto tenant_id): los
     * únicos globales dejarían de poder crearse. El pre-check corre antes de escribir nada, así
     * que falla limpio con la lista de duplicados y la base queda intacta.
     * Los índices y FK se chequean antes de tocarlos para poder reintentar un down() interrumpido.
     */
    public function down()
    {
        $this->assertNoDuplicatesAcrossTenants();

        if ($this->constraintExists('patients', 'fk_patients_tenant')) {
            $this->db->query("ALTER TABLE patients DROP FOREIGN KEY fk_patients_tenant");
        }
        if ($this->columnExists('patients', 'tenant_id')) {
            $this->db->query("ALTER TABLE patients DROP COLUMN tenant_id");
        }

        if ($this->constraintExists('users', 'fk_users_tenant')) {
            $this->db->query("ALTER TABLE users DROP FOREIGN KEY fk_users_tenant");
        }
        if (!$this->indexExists('users', 'email')) {
            $this->db->query("ALTER TABLE users ADD UNIQUE KEY email (email)");
        }
        if (!$this->indexExists('users', 'uq_users_dni')) {
            $this->db->query("ALTER TABLE users ADD UNIQUE KEY uq_users_dni (dni)");
        }
        if ($this->indexExists('users', 'uq_users_tenant_email')) {
            $this->db->query("ALTER TABLE users DROP INDEX uq_users_tenant_email");
        }
        if ($this->indexExists('users', 'uq_users_tenant_dni')) {
            $this->db->query("ALTER TABLE users DROP INDEX uq_users_tenant_dni");
        }
        if ($this->columnExists('users', 'tenant_id')) {
            $this->db->query("ALTER TABLE users DROP COLUMN tenant_id");
        }
    }

    private function assertNoDuplicatesAcrossTenants(): void
    {
        $duplicates = [];

        foreach (['email', 'dni'] as $column) {
            $rows = $this->db->query(
                "SELECT {$column} AS value, GROUP_CONCAT(DISTINCT tenant_id ORDER BY tenant_id) AS tenants
                 FROM users GROUP BY {$column} HAVING COUNT(*) > 1"
            )->getResultArray();

            foreach ($rows as $row) {
                $duplicates[] = sprintf('%s "%s" (tenants: %s)', $column, $row['value'], str_replace(',', ', ', $row['tenants']));
            }
        }

        if ($duplicates !== []) {
            throw new RuntimeException(
                'No se puede revertir: existen emails/DNI duplicados entre tenants. '
                . 'Unificá o eliminá esos usuarios y volvé a revertir. Duplicados: ' . implode('; ', $duplicates) . '.'
            );
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

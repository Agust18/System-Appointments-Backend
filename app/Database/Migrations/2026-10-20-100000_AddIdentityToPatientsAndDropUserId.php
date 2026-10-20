<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * El paciente deja de ser usuario: `patients` guarda su propia identidad
 * (nombre, apellido, email, DNI, teléfono) y pierde `user_id`.
 *
 * - Copia la identidad desde `users` antes de soltar `user_id`.
 * - UNIQUE(tenant_id, dni); el email no es único (KEY común para búsquedas).
 * - Los `users` con rol paciente NO se borran acá (lo hace la fase 2).
 *
 * `down()` vincula cada paciente con el `users` de su mismo DNI y recrea un usuario sin cuenta
 * para los que no tengan (con el email solo si no choca con otro usuario).
 */
class AddIdentityToPatientsAndDropUserId extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE patients
            ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER tenant_id,
            ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name,
            ADD COLUMN email VARCHAR(150) NULL AFTER last_name,
            ADD COLUMN dni INT NOT NULL DEFAULT 0 AFTER email,
            ADD COLUMN phone VARCHAR(20) NULL AFTER dni");

        $this->db->query("UPDATE patients P
            INNER JOIN users U ON U.id = P.user_id
            SET P.first_name = U.first_name, P.last_name = U.last_name, P.email = U.email,
                P.dni = U.dni, P.phone = U.phone");

        $this->assertNoDuplicateDnis();

        $this->db->query("ALTER TABLE patients ALTER COLUMN dni DROP DEFAULT");
        $this->db->query("ALTER TABLE patients ADD UNIQUE KEY uq_patients_tenant_dni (tenant_id, dni)");
        $this->db->query("ALTER TABLE patients ADD KEY idx_patients_tenant_email (tenant_id, email)");

        $this->db->query("ALTER TABLE patients DROP FOREIGN KEY fk_patients_user_id");

        foreach (['uq_patients_user_id', 'fk_patients_user_id'] as $index) {
            if ($this->indexExists('patients', $index)) {
                $this->db->query("ALTER TABLE patients DROP INDEX {$index}");
            }
        }

        $this->db->query("ALTER TABLE patients DROP COLUMN user_id");
    }

    private function indexExists(string $table, string $index): bool
    {
        return $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])->getResultArray() !== [];
    }

    public function down()
    {
        $this->db->query("ALTER TABLE patients ADD COLUMN user_id INT UNSIGNED NULL AFTER tenant_id");

        $this->db->query("UPDATE patients P
            INNER JOIN users U ON U.dni = P.dni AND U.tenant_id = P.tenant_id
            SET P.user_id = U.id");

        $this->recreateAccountlessUsersForOrphans();

        $this->db->query("ALTER TABLE patients MODIFY user_id INT UNSIGNED NOT NULL");
        $this->db->query("ALTER TABLE patients ADD UNIQUE KEY uq_patients_user_id (user_id)");
        // Índice propio de la FK (como en el esquema original): la migración anterior suelta el UNIQUE y la FK lo necesita.
        $this->db->query("ALTER TABLE patients ADD KEY fk_patients_user_id (user_id)");
        $this->db->query("ALTER TABLE patients ADD CONSTRAINT fk_patients_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE");

        // La FK de tenant_id usa el UNIQUE como índice: se le devuelve el suyo antes de soltarlo.
        if (!$this->indexExists('patients', 'fk_patients_tenant')) {
            $this->db->query("ALTER TABLE patients ADD KEY fk_patients_tenant (tenant_id)");
        }
        $this->db->query("ALTER TABLE patients DROP INDEX idx_patients_tenant_email");
        $this->db->query("ALTER TABLE patients DROP INDEX uq_patients_tenant_dni");
        $this->db->query("ALTER TABLE patients DROP COLUMN phone, DROP COLUMN dni, DROP COLUMN email, DROP COLUMN last_name, DROP COLUMN first_name");
    }

    /**
     * Pacientes sin un `users` con su DNI (los creados después de `up()`): se les recrea un usuario sin
     * cuenta (sin contraseña). El email solo se copia si no choca con otro usuario del Tenant.
     */
    private function recreateAccountlessUsersForOrphans(): void
    {
        $this->db->query("INSERT INTO users (tenant_id, first_name, last_name, email, phone, dni, role_id, is_active)
            SELECT P.tenant_id, P.first_name, P.last_name, NULL, P.phone, P.dni, (SELECT R.id FROM roles R WHERE R.id = 3), 1
            FROM patients P
            WHERE P.user_id IS NULL");

        $this->db->query("UPDATE IGNORE users U
            INNER JOIN patients P ON P.dni = U.dni AND P.tenant_id = U.tenant_id
            SET U.email = P.email
            WHERE U.email IS NULL AND U.password IS NULL");

        $this->db->query("UPDATE patients P
            INNER JOIN users U ON U.dni = P.dni AND U.tenant_id = P.tenant_id
            SET P.user_id = U.id
            WHERE P.user_id IS NULL");
    }

    private function assertNoDuplicateDnis(): void
    {
        $rows = $this->db->query(
            'SELECT tenant_id, dni FROM patients GROUP BY tenant_id, dni HAVING COUNT(*) > 1'
        )->getResultArray();

        if ($rows === []) {
            return;
        }

        $parts = array_map(fn ($r) => sprintf('tenant %s dni %s', $r['tenant_id'], $r['dni']), $rows);

        throw new RuntimeException(
            'No se puede crear UNIQUE(tenant_id, dni) en patients: hay DNIs repetidos. Duplicados: ' . implode('; ', $parts) . '.'
        );
    }
}

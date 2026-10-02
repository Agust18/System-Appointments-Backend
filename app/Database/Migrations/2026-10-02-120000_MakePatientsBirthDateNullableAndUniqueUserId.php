<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * - patients.birth_date pasa a ser nullable: la reserva pública no la pide.
 * - patients.user_id pasa a ser UNIQUE: garantiza 1 user → 1 patient aunque
 *   dos solicitudes concurrentes pasen el chequeo del validador. Si algún día
 *   se implementan perfiles de familia (1 user → N patients), se elimina.
 *
 * El pre-check corre antes de escribir: con duplicados aborta listándolos.
 */
class MakePatientsBirthDateNullableAndUniqueUserId extends Migration
{
    public function up()
    {
        $this->assertNoDuplicateUserIds();

        $this->db->query('ALTER TABLE patients MODIFY birth_date DATE NULL');
        $this->db->query('ALTER TABLE patients ADD UNIQUE KEY uq_patients_user_id (user_id)');
    }

    /**
     * Los pacientes sin fecha de nacimiento quedan con una fecha neutra (1900-01-01).
     */
    public function down()
    {
        $this->db->query('ALTER TABLE patients DROP INDEX uq_patients_user_id');
        $this->db->query("UPDATE patients SET birth_date = '1900-01-01' WHERE birth_date IS NULL");
        $this->db->query('ALTER TABLE patients MODIFY birth_date DATE NOT NULL');
    }

    private function assertNoDuplicateUserIds(): void
    {
        $rows = $this->db->query(
            'SELECT user_id, GROUP_CONCAT(id ORDER BY id) AS ids FROM patients GROUP BY user_id HAVING COUNT(*) > 1'
        )->getResultArray();

        if ($rows === []) {
            return;
        }

        $parts = [];

        foreach ($rows as $row) {
            $parts[] = sprintf('users.id %s (patients.id: %s)', $row['user_id'], str_replace(',', ', ', $row['ids']));
        }

        throw new RuntimeException(
            'No se puede crear el UNIQUE de patients.user_id: hay usuarios con más de un paciente. '
            . 'Unificá esos pacientes y volvé a migrar. Duplicados: ' . implode('; ', $parts) . '.'
        );
    }
}

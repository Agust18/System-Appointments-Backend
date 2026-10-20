<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Acceso del paciente a su turno desde `/consulta` (sin cuenta).
 *
 * - access_code_hash: SHA-256 del código corto (8 caracteres) que se usa junto con el DNI.
 * - access_token_hash: SHA-256 del token largo que viaja en el link del email (`/consulta?t=...`).
 *
 * Nunca se guarda el valor en claro: se le muestra al paciente una sola vez, al reservar. Los turnos
 * existentes quedan en NULL y no se pueden consultar (no hay pacientes reales que migrar).
 */
class AddAccessToAppointments extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE appointments
                ADD COLUMN access_code_hash CHAR(64) NULL AFTER status,
                ADD COLUMN access_token_hash CHAR(64) NULL AFTER access_code_hash,
                ADD UNIQUE KEY uq_appointments_access_token (access_token_hash)"
        );
    }

    public function down()
    {
        if ($this->indexExists('appointments', 'uq_appointments_access_token')) {
            $this->db->query("ALTER TABLE appointments DROP INDEX uq_appointments_access_token");
        }
        if ($this->columnExists('appointments', 'access_token_hash')) {
            $this->db->query("ALTER TABLE appointments DROP COLUMN access_token_hash");
        }
        if ($this->columnExists('appointments', 'access_code_hash')) {
            $this->db->query("ALTER TABLE appointments DROP COLUMN access_code_hash");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->db->query(
            "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        )->getRow()->total > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        return $this->db->query(
            "SELECT COUNT(*) AS total FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]
        )->getRow()->total > 0;
    }
}

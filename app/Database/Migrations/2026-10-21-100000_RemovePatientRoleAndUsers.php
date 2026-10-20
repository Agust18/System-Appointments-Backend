<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El paciente dejó de ser un usuario: se borran los `users` con rol 3 (paciente) y la fila 3 de `roles`.
 *
 * - No hay pacientes reales como usuarios (la ficha vive en `patients`), así que no se pierde información.
 * - El id 3 NO se reutiliza (los roles quedan 1, 2 y 4).
 * - Los tokens de esos usuarios caen en cascada (FK de user_verification_tokens).
 *
 * `down()` solo vuelve a crear la fila del rol; los usuarios borrados no se recuperan.
 */
class RemovePatientRoleAndUsers extends Migration
{
    private const PATIENT_ROLE_ID = 3;

    public function up()
    {
        $this->db->query('DELETE FROM users WHERE role_id = ?', [self::PATIENT_ROLE_ID]);
        $this->db->query('DELETE FROM roles WHERE id = ?', [self::PATIENT_ROLE_ID]);
    }

    public function down()
    {
        $this->db->query(
            'INSERT INTO roles (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)',
            [self::PATIENT_ROLE_ID, 'patient']
        );
    }
}

<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * users.email pasa a admitir NULL: recepción puede dar de alta pacientes sin email.
 * El UNIQUE(tenant_id, email) se mantiene; MySQL admite varios NULL en un UNIQUE.
 *
 * `down()` aborta si hay usuarios sin email: no hay un valor válido para completarlos.
 */
class MakeUsersEmailNullable extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `users` MODIFY `email` varchar(150) COLLATE utf8mb4_0900_ai_ci NULL");
    }

    public function down()
    {
        $withoutEmail = (int) $this->db->query("SELECT COUNT(*) AS total FROM `users` WHERE `email` IS NULL")->getRow()->total;

        if ($withoutEmail > 0) {
            throw new \RuntimeException(
                "No se puede volver users.email a NOT NULL: hay {$withoutEmail} usuario(s) sin email. Cargales uno antes de revertir."
            );
        }

        $this->db->query("ALTER TABLE `users` MODIFY `email` varchar(150) COLLATE utf8mb4_0900_ai_ci NOT NULL");
    }
}

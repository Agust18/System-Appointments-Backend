<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsVerifiedToUsers extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE `users` 
            ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 
            AFTER `is_active`
        ");

        // Marcar a todos los usuarios existentes como verificados
        $this->db->query("UPDATE `users` SET `is_verified` = 1");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `users` DROP COLUMN `is_verified`");
    }
}
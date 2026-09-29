<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReplaceUsernameWithFirstLastName extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `username`");
        $this->db->query("ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `first_name`");

        $this->db->query("
            UPDATE `users`
            SET
                `first_name` = TRIM(SUBSTRING_INDEX(`username`, ' ', 1)),
                `last_name` = TRIM(SUBSTRING(`username`, LENGTH(SUBSTRING_INDEX(`username`, ' ', 1)) + 1))
        ");

        $this->db->query("ALTER TABLE `users` DROP INDEX `username`");
        $this->db->query("ALTER TABLE `users` DROP COLUMN `username`");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(50) NOT NULL DEFAULT '' AFTER `id`");

        $this->db->query("
            UPDATE `users`
            SET `username` = TRIM(CONCAT(`first_name`, ' ', `last_name`))
        ");

        $this->db->query("ALTER TABLE `users` ADD UNIQUE KEY `username` (`username`)");
        $this->db->query("ALTER TABLE `users` DROP COLUMN `first_name`");
        $this->db->query("ALTER TABLE `users` DROP COLUMN `last_name`");
    }
}

<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserVerificationTokensTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE `user_verification_tokens` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int UNSIGNED NOT NULL,
              `token` varchar(255) NOT NULL,
              `type` enum('email_verification','password_reset') NOT NULL,
              `expires_at` datetime NOT NULL,
              `used_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `token` (`token`),
              KEY `idx_user_type` (`user_id`,`type`),
              CONSTRAINT `uvt_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `user_verification_tokens`");
    }
}
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tokens de sesión de los platform_admins. Tabla propia, separada de los tokens de `users`,
 * para que un token de plataforma nunca se resuelva como usuario de un Tenant ni al revés.
 */
class CreatePlatformAdminTokensTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE `platform_admin_tokens` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `platform_admin_id` int UNSIGNED NOT NULL,
              `token` varchar(255) NOT NULL,
              `expires_at` datetime NOT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_platform_admin_tokens_token` (`token`),
              KEY `idx_platform_admin_tokens_admin` (`platform_admin_id`),
              CONSTRAINT `fk_pat_platform_admin` FOREIGN KEY (`platform_admin_id`) REFERENCES `platform_admins` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `platform_admin_tokens`");
    }
}

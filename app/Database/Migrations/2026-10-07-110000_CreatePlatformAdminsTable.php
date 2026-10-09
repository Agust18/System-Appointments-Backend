<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Administradores de la plataforma (SuperAdmin). Tabla global: sin `tenant_id` a propósito
 * (ADR 003), para que ninguna consulta de Tenant pueda tratarlos como usuarios de un Tenant.
 */
class CreatePlatformAdminsTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE `platform_admins` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `email` varchar(255) NOT NULL,
              `first_name` varchar(100) NOT NULL,
              `last_name` varchar(100) NOT NULL,
              `password` varchar(255) NOT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_platform_admins_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `platform_admins`");
    }
}

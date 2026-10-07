<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE `tenants` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `slug` varchar(63) NOT NULL,
              `name` varchar(150) NOT NULL,
              `status` enum('active','suspended') NOT NULL DEFAULT 'active',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_tenants_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `tenants`");
    }
}

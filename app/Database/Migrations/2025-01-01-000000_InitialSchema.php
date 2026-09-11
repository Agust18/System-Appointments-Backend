<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InitialSchema extends Migration
{
    public function up()
    {
        // 1. roles
        $this->db->query("
            CREATE TABLE `roles` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 2. users
        $this->db->query("
            CREATE TABLE `users` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `email` varchar(150) NOT NULL,
              `password` varchar(255) NOT NULL,
              `dni` int NOT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT '1',
              `token` varchar(255) DEFAULT NULL,
              `token_expiration_date` datetime DEFAULT NULL,
              `role_id` int UNSIGNED DEFAULT NULL,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `username` (`username`),
              UNIQUE KEY `email` (`email`),
              KEY `role_id` (`role_id`),
              CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 3. specialties
        $this->db->query("
            CREATE TABLE `specialties` (
              `id` int NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `description` text,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 4. services
        $this->db->query("
            CREATE TABLE `services` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `description` text,
              `duration_minutes` int UNSIGNED NOT NULL,
              `buffer_minutes` int UNSIGNED NOT NULL DEFAULT '0',
              `price` decimal(10,2) NOT NULL DEFAULT '0.00',
              `slot_step_minutes` int UNSIGNED NOT NULL DEFAULT '10',
              `is_active` tinyint(1) NOT NULL DEFAULT '1',
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 5. medicals
        $this->db->query("
            CREATE TABLE `medicals` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int UNSIGNED NOT NULL,
              `tuition` varchar(50) NOT NULL,
              `specialty_id` int NOT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `is_active` tinyint(1) DEFAULT '1',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `specialty_id` (`specialty_id`),
              CONSTRAINT `medicals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
              CONSTRAINT `medicals_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 6. patients
        $this->db->query("
            CREATE TABLE `patients` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int UNSIGNED NOT NULL,
              `birth_date` date NOT NULL,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `fk_patients_user_id` (`user_id`),
              CONSTRAINT `fk_patients_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 7. medical_schedules
        $this->db->query("
            CREATE TABLE `medical_schedules` (
              `id` int NOT NULL AUTO_INCREMENT,
              `medical_id` int NOT NULL,
              `day_of_week` tinyint NOT NULL,
              `start_time` time NOT NULL,
              `end_time` time NOT NULL,
              `is_active` tinyint(1) DEFAULT '1',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `medical_id` (`medical_id`),
              CONSTRAINT `medical_schedules_ibfk_1` FOREIGN KEY (`medical_id`) REFERENCES `medicals` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 8. medical_service
        $this->db->query("
            CREATE TABLE `medical_service` (
              `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
              `medical_id` int NOT NULL,
              `service_id` int UNSIGNED NOT NULL,
              `custom_price` decimal(10,2) DEFAULT NULL,
              `custom_duration_minutes` int UNSIGNED DEFAULT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT '1',
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_medical_service` (`medical_id`,`service_id`),
              KEY `fk_ms_service` (`service_id`),
              CONSTRAINT `fk_ms_medical` FOREIGN KEY (`medical_id`) REFERENCES `medicals` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_ms_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // 9. appointments
        $this->db->query("
            CREATE TABLE `appointments` (
              `id` int NOT NULL AUTO_INCREMENT,
              `medical_id` int NOT NULL,
              `patient_id` int NOT NULL,
              `service_id` int UNSIGNED DEFAULT NULL,
              `fecha` date NOT NULL,
              `hora` time NOT NULL,
              `hora_fin` time DEFAULT NULL,
              `nota` text,
              `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `patient_id` (`patient_id`),
              KEY `fk_appt_service` (`service_id`),
              KEY `idx_medical_fecha_status` (`medical_id`,`fecha`,`status`),
              CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`medical_id`) REFERENCES `medicals` (`id`) ON DELETE CASCADE,
              CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    public function down()
    {
        // Orden inverso por las foreign keys
        $this->db->query("DROP TABLE IF EXISTS `appointments`");
        $this->db->query("DROP TABLE IF EXISTS `medical_service`");
        $this->db->query("DROP TABLE IF EXISTS `medical_schedules`");
        $this->db->query("DROP TABLE IF EXISTS `patients`");
        $this->db->query("DROP TABLE IF EXISTS `medicals`");
        $this->db->query("DROP TABLE IF EXISTS `services`");
        $this->db->query("DROP TABLE IF EXISTS `specialties`");
        $this->db->query("DROP TABLE IF EXISTS `users`");
        $this->db->query("DROP TABLE IF EXISTS `roles`");
    }
}
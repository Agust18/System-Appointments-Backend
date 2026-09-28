<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMedicalDayIndexToMedicalSchedules extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE `medical_schedules`
            ADD INDEX `idx_medical_day` (`medical_id`, `day_of_week`)
        ");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `medical_schedules` DROP INDEX `idx_medical_day`");
    }
}

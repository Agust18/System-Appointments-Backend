<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El paso de la grilla es siempre duración + buffer del servicio (con override
 * del médico), así que services.slot_step_minutes es redundante.
 */
class DropSlotStepMinutesFromServices extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE services DROP COLUMN slot_step_minutes');
    }

    public function down()
    {
        $this->db->query("ALTER TABLE services ADD COLUMN slot_step_minutes INT UNSIGNED NOT NULL DEFAULT 15 AFTER price");
    }
}

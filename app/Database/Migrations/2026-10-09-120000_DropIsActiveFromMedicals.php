<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Elimina medicals.is_active: la disponibilidad de un médico se decide solo con
 * users.is_active (un médico inactivo no inicia sesión, no figura en listados de
 * reserva y no puede recibir turnos).
 */
class DropIsActiveFromMedicals extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('medicals', 'is_active');
    }

    public function down()
    {
        $this->forge->addColumn('medicals', [
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => 1,
            ],
        ]);

        $this->db->query("
            UPDATE medicals m
            INNER JOIN users u ON u.id = m.user_id
            SET m.is_active = u.is_active
        ");
    }
}

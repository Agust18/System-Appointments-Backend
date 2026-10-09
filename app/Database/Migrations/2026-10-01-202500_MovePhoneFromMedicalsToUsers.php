<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MovePhoneFromMedicalsToUsers extends Migration
{
    public function up()
    {
        
        $this->forge->addColumn('users', [
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'email',
            ],
        ]);

        
        $this->db->query("
            UPDATE users u
            INNER JOIN medicals m ON m.user_id = u.id
            SET u.phone = m.phone
            WHERE m.phone IS NOT NULL
        ");

        
        $this->forge->dropColumn('medicals', 'phone');
    }

    public function down()
    {
        
        $this->forge->addColumn('medicals', [
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'user_id',
            ],
        ]);

        
        $this->db->query("
            UPDATE medicals m
            INNER JOIN users u ON u.id = m.user_id
            SET m.phone = u.phone
            WHERE u.phone IS NOT NULL
        ");

        // 3. Eliminar phone de users
        $this->forge->dropColumn('users', 'phone');
    }
}
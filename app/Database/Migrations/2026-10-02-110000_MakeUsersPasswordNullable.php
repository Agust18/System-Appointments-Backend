<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * users.password pasa a ser nullable: NULL = usuario sin cuenta (creado por
 * reserva pública o por recepción). No existe una columna has_account: la
 * única fuente de verdad es password IS NULL.
 */
class MakeUsersPasswordNullable extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }

    /**
     * Los usuarios sin cuenta quedan con password vacío: ningún password_verify
     * puede coincidir, así que siguen sin poder iniciar sesión.
     */
    public function down()
    {
        $this->db->query("UPDATE users SET password = '' WHERE password IS NULL");
        $this->db->query('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');
    }
}

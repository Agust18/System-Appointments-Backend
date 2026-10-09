<?php

namespace App\Database\Seeds;

use App\Constants\Role;
use CodeIgniter\Database\Seeder;

/**
 * Siembra los roles con los ids de App\Constants\Role. Idempotente: se puede correr en cada deploy.
 *
 * Nunca mueve ids (users.role_id apunta por FK): solo renombra roles.name. Como roles.name es UNIQUE,
 * si el nombre deseado lo tiene otro id (p. ej. 2/3 invertidos) ese otro se renombra antes a un
 * placeholder, que a su vez se corrige cuando le toca su turno.
 *
 *   php spark db:seed RolesSeeder
 */
final class RolesSeeder extends Seeder
{
    private const ROLES = [
        Role::ADMIN => 'admin',
        Role::MEDICAL => 'medical',
        Role::PATIENT => 'patient',
        Role::RECEPTION => 'reception',
    ];

    public function run(): void
    {
        $this->db->transBegin();
        try {
            foreach (self::ROLES as $id => $name) {
                $this->db->query(
                    'UPDATE roles SET name = CONCAT(?, id) WHERE name = ? AND id <> ?',
                    ['__tmp_', $name, $id]
                );
                $this->db->query(
                    'INSERT INTO roles (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)',
                    [$id, $name]
                );
            }
            $this->db->transCommit();
            echo 'Roles OK: ' . count(self::ROLES) . " roles sembrados.\n";
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}

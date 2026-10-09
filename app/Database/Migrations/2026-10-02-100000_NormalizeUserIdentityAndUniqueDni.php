<?php

namespace App\Database\Migrations;

use App\Support\IdentityNormalizer;
use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Normaliza email y teléfono de los users existentes y agrega UNIQUE a users.dni.
 *
 * Los pre-checks corren antes de escribir nada: si hay DNIs duplicados, o dos
 * emails que colisionan al normalizarlos, la migración aborta con la lista y la
 * base queda intacta. Hay que resolver esos casos a mano y volver a migrar.
 */
class NormalizeUserIdentityAndUniqueDni extends Migration
{
    public function up()
    {
        $users = $this->db->query('SELECT id, email, phone FROM users')->getResultArray();

        $this->assertNoDuplicateEmails($users);
        $this->assertNoDuplicateDnis();

        foreach ($users as $user) {
            $email = IdentityNormalizer::email($user['email']) ?? $user['email'];
            $phone = IdentityNormalizer::phone($user['phone']);

            if ($email !== $user['email'] || $phone !== $user['phone']) {
                $this->db->query(
                    'UPDATE users SET email = ?, phone = ? WHERE id = ?',
                    [$email, $phone, $user['id']]
                );
            }
        }

        $this->db->query('ALTER TABLE users ADD UNIQUE KEY uq_users_dni (dni)');
    }

    /**
     * La normalización de emails y teléfonos no se revierte (no se conserva el valor original).
     */
    public function down()
    {
        $this->db->query('ALTER TABLE users DROP INDEX uq_users_dni');
    }

    /**
     * El UNIQUE de email usa una collation NO PAD: "a@b.com " y "a@b.com" hoy
     * conviven y al hacer trim colisionarían.
     */
    private function assertNoDuplicateEmails(array $users): void
    {
        $idsByEmail = [];

        foreach ($users as $user) {
            $email = IdentityNormalizer::email($user['email']) ?? $user['email'];
            $idsByEmail[$email][] = $user['id'];
        }

        $duplicates = array_filter($idsByEmail, static fn (array $ids): bool => count($ids) > 1);

        if ($duplicates !== []) {
            throw new RuntimeException(
                'No se puede normalizar users.email: hay emails que colisionan al quitar espacios/mayúsculas. '
                . 'Resolvelos a mano y volvé a migrar. ' . $this->describe($duplicates)
            );
        }
    }

    private function assertNoDuplicateDnis(): void
    {
        $rows = $this->db->query(
            'SELECT dni, GROUP_CONCAT(id ORDER BY id) AS ids FROM users GROUP BY dni HAVING COUNT(*) > 1'
        )->getResultArray();

        if ($rows !== []) {
            $duplicates = [];

            foreach ($rows as $row) {
                $duplicates[$row['dni']] = explode(',', $row['ids']);
            }

            throw new RuntimeException(
                'No se puede crear el UNIQUE de users.dni: hay DNIs duplicados. '
                . 'Unificá o corregí esos usuarios y volvé a migrar. ' . $this->describe($duplicates)
            );
        }
    }

    /**
     * @param array<string|int, array<int|string>> $duplicates valor duplicado => ids de users
     */
    private function describe(array $duplicates): string
    {
        $parts = [];

        foreach ($duplicates as $value => $ids) {
            $parts[] = sprintf('"%s" (users.id: %s)', $value, implode(', ', $ids));
        }

        return 'Duplicados: ' . implode('; ', $parts) . '.';
    }
}

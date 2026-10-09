<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Multitenant, Paso 2 (cierre): quita el DEFAULT 1 TRANSITORIO de tenant_id en las 9 tablas.
 *
 * Desde acá un INSERT que omita tenant_id falla (NOT NULL sin default) en vez de caer en silencio
 * en el Tenant 1. Es seguro en producción: todas las filas ya tienen tenant_id y el código de la
 * app escribe tenant_id explícito en todos los INSERT. Solo cambia el esquema (metadatos).
 *
 * Idempotente: chequea el default antes de tocar, así que un up() o down() interrumpido se puede
 * reintentar. down() vuelve al DEFAULT 1 transitorio.
 */
class DropTenantIdDefaults extends Migration
{
    private const TABLES = [
        'users',
        'patients',
        'specialties',
        'services',
        'medicals',
        'medical_schedules',
        'medical_service',
        'appointments',
        'user_verification_tokens',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            if ($this->hasDefault($table)) {
                $this->db->query("ALTER TABLE {$table} ALTER COLUMN tenant_id DROP DEFAULT");
            }
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            if ($this->columnExists($table) && !$this->hasDefault($table)) {
                $this->db->query("ALTER TABLE {$table} ALTER COLUMN tenant_id SET DEFAULT 1");
            }
        }
    }

    private function columnExists(string $table): bool
    {
        return $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getResultArray() !== [];
    }

    private function hasDefault(string $table): bool
    {
        $column = $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getRow();

        return $column !== null && $column->Default !== null;
    }
}

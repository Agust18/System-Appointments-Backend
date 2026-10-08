<?php

namespace Tests\Support\Database;

use CodeIgniter\Test\DatabaseTestTrait;

/**
 * DatabaseTestTrait sin reconstruir el esquema en cada test: rollback + migrate completo la primera
 * vez (y cada vez que el esquema ya no coincide), y después solo se vacían los datos.
 * No usar en tests que alteran el esquema a mano: esos conservan DatabaseTestTrait con $refresh.
 */
trait FastDatabaseTestTrait
{
    use DatabaseTestTrait;

    private static ?string $schema = null;

    protected function setUpDatabase()
    {
        $this->loadDependencies();

        if (self::$schema === null || self::$schema !== $this->currentSchema()) {
            $this->regressDatabase();
            $this->migrateDatabase();
            self::$schema = $this->currentSchema();
        }

        $this->truncateData();
        $this->setUpSeed();
    }

    private function currentSchema(): string
    {
        $this->db->query('SET SESSION group_concat_max_len = 1000000');

        return (string) $this->db->query(
            "SELECT CONCAT(
                (SELECT MD5(GROUP_CONCAT(table_name, column_name, column_type, is_nullable, IFNULL(column_default, '') ORDER BY table_name, column_name))
                   FROM information_schema.columns WHERE table_schema = DATABASE()),
                (SELECT MD5(GROUP_CONCAT(table_name, index_name, column_name, non_unique ORDER BY table_name, index_name, seq_in_index))
                   FROM information_schema.statistics WHERE table_schema = DATABASE())
            ) AS s"
        )->getRow()->s;
    }

    private function truncateData(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($this->db->query('SHOW TABLES')->getResultArray() as $row) {
                $table = (string) reset($row);
                if ($table !== 'migrations') {
                    $this->db->query("TRUNCATE TABLE `{$table}`");
                }
            }
            $this->db->query("INSERT INTO tenants (id, slug, name, status) VALUES (1, 'demo', 'Demo', 'active')");
            $this->db->query("INSERT INTO tenant_settings (tenant_id, name) VALUES (1, 'Demo')");
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}

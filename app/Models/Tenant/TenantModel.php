<?php

namespace App\Models\Tenant;

use App\Constants\TenantStatus;
use App\Converter\Tenant\PrimitiveToTenantConverter;
use App\Entity\Tenant\Tenant;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * La tabla `tenants` es global: no lleva `tenant_id` ni filtra por TenantContext.
 */
final class TenantModel
{
    private BaseConnection $db;
    private PrimitiveToTenantConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToTenantConverter();
    }

    public function insert(Tenant $tenant): Tenant
    {
        $query = "INSERT INTO tenants (slug, name, status, created_at, updated_at) VALUES (?,?,?,?,?)";

        $this->db->query($query, [
            $tenant->getSlug(),
            $tenant->getName(),
            $tenant->getStatus(),
            $tenant->getCreatedAt()->format('Y-m-d H:i:s'),
            $tenant->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $this->find($this->db->insertID());
    }

    public function find(int $id): ?Tenant
    {
        $result = $this->db->query("SELECT * FROM tenants WHERE id = ?", [$id]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }

    /**
     * Tenants que pueden operar (no suspendidos), para los procesos que recorren todos los
     * Tenants sin una request (recordatorios, limpieza de tokens).
     *
     * @return Tenant[]
     */
    public function findAllActive(): array
    {
        $result = $this->db->query("SELECT * FROM tenants WHERE status = ? ORDER BY id ASC", [TenantStatus::ACTIVE]);

        $tenants = [];
        foreach ($result->getResult() as $primitive) {
            $tenants[] = $this->converter->convert($primitive);
        }

        return $tenants;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        $result = $this->db->query("SELECT * FROM tenants WHERE slug = ?", [$slug]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }
}

<?php

namespace App\Models\Tenant;

use App\Constants\TenantStatus;
use App\Converter\Tenant\PrimitiveToTenantConverter;
use App\Dto\Request\Platform\TenantFilterRequest;
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

    public function existsBySlug(string $slug): bool
    {
        $result = $this->db->query("SELECT 1 FROM tenants WHERE slug = ? LIMIT 1", [$slug]);

        return $result->getRow() !== null;
    }

    /**
     * Solo persiste nombre y estado: el slug no se modifica.
     */
    public function update(Tenant $tenant): ?Tenant
    {
        $this->db->query(
            "UPDATE tenants SET name = ?, status = ?, updated_at = NOW() WHERE id = ?",
            [$tenant->getName(), $tenant->getStatus(), $tenant->getId()]
        );

        return $this->find($tenant->getId());
    }

    /**
     * Borra un Tenant recién creado. Solo para el alta (plan B si falla la creación del ADMIN);
     * las FK con `tenant_id` impiden borrar un Tenant que ya tiene datos.
     */
    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM tenants WHERE id = ?", [$id]);
    }

    /**
     * @return Tenant[]
     */
    public function search(TenantFilterRequest $request): array
    {
        [$where, $parameters] = $this->buildFilter($request);

        $query = "SELECT * FROM tenants " . $where . "ORDER BY id ASC LIMIT ?, ? ";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $tenants = [];
        foreach ($this->db->query($query, $parameters)->getResult() as $primitive) {
            $tenants[] = $this->converter->convert($primitive);
        }

        return $tenants;
    }

    public function countByFilter(TenantFilterRequest $request): int
    {
        [$where, $parameters] = $this->buildFilter($request);

        $result = $this->db->query("SELECT COUNT(*) AS total FROM tenants " . $where, $parameters);

        return (int) $result->getRow()->total;
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildFilter(TenantFilterRequest $request): array
    {
        $where = "WHERE 1 = 1 ";
        $parameters = [];

        if ($request->hasSearch()) {
            $where .= " AND (name LIKE ? OR slug LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($request->hasStatus()) {
            $where .= " AND status = ? ";
            $parameters[] = $request->getStatus();
        }

        return [$where, $parameters];
    }
}

<?php

namespace App\Models\Service;

use App\Converter\Service\PrimitiveToServiceConverter;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Entity\Service\Service;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class ServiceModel
{
    private BaseConnection $db;
    private PrimitiveToServiceConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToServiceConverter();
    }

    public function insert(Service $service): Service
    {
        $query = "INSERT INTO services (tenant_id, name, description, duration_minutes, buffer_minutes, price, is_active, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->db->query($query, [
            TenantContext::id(),
            $service->getName(),
            $service->getDescription(),
            $service->getDurationMinutes(),
            $service->getBufferMinutes(),
            $service->getPrice(),
            $service->isActive()
        ]);

        $id = $this->db->insertID();

        return $this->find($id);
    }

    public function update(Service $service): Service
    {
        $query = "UPDATE services
                  SET name = ?, description = ?, duration_minutes = ?, buffer_minutes = ?, price = ?, is_active = ?, updated_at = NOW()
                  WHERE id = ? AND tenant_id = ?";

        $this->db->query($query, [
            $service->getName(),
            $service->getDescription(),
            $service->getDurationMinutes(),
            $service->getBufferMinutes(),
            $service->getPrice(),
            $service->isActive(),
            $service->getId(),
            TenantContext::id()
        ]);

        return $this->find($service->getId());
    }

    public function find(int $id): ?Service
    {
        $query = "SELECT * FROM services WHERE id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

     public function findByName(string $name): ?Service
    {
        $query = "SELECT * FROM services WHERE name = ? AND is_active = 1 AND tenant_id = ?";
        $result = $this->db->query($query, [$name, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findAllActive(): array
    {
        $query = "SELECT * FROM services WHERE is_active = 1 AND tenant_id = ? ORDER BY name ASC";
        $result = $this->db->query($query, [TenantContext::id()]);
        $primitives = $result->getResult();

        $services = [];
        foreach ($primitives as $primitive) {
            $services[] = $this->converter->convert($primitive);
        }

        return $services;
    }

    public function search(ServiceFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT * FROM services " . $whereQuery . " ORDER BY id ASC LIMIT ?, ?";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $result = $this->db->query($query, $parameters);
        $primitives = $result->getResult();

        $services = [];
        foreach ($primitives as $primitive) {
            $services[] = $this->converter->convert($primitive);
        }

        return $services;
    }

    public function count(ServiceFilterRequest $request): int
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT COUNT(*) as total FROM services " . $whereQuery;
        $result = $this->db->query($query, $parameters);
        $row = $result->getRow();

        return (int) $row->total;
    }

    private function buildWhereClause(ServiceFilterRequest $request): array
    {
        $parameters = [TenantContext::id()];
        $whereQuery = "WHERE tenant_id = ? ";

        if ($request->hasIsActive()) {
            $whereQuery .= " AND is_active = ? ";
            $parameters[] = $request->getIsActive() ? 1 : 0;
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (name LIKE ? OR description LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        return [$whereQuery, $parameters];
    }


    public function delete(int $id): void
    {
        $query = "UPDATE services SET is_active = 0, updated_at = NOW() WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }


    public function hardDelete(int $id): void
    {
        $query = "DELETE FROM services WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }
}

<?php

namespace App\Models\Medical;

use App\Converter\Medical\PrimitiveToMedicalConverter;
use App\Converter\Medical\Projection\PrimitiveToMedicalProjectionConverter;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Entity\Medical\Medical;
use App\Entity\Medical\Projection\MedicalProjection;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class MedicalModel
{
    private BaseConnection $db;
    private PrimitiveToMedicalConverter $converter;
    private PrimitiveToMedicalProjectionConverter $converterprojection;

    public function __construct()
    {
        $this->converter = new PrimitiveToMedicalConverter();
        $this->converterprojection = new PrimitiveToMedicalProjectionConverter();
        $this->db = Database::connect();
    }
    public function insert(Medical $medical): Medical
    {
        $query = "INSERT INTO medicals (tenant_id, user_id, tuition, specialty_id) values (?,?,?,?)";
        $this->db->query($query, [
            TenantContext::id(),
            $medical->getUserId(),
            $medical->getTuition(),
            $medical->getSpecialtyId()
        ]);
        $id = $this->db->insertID();
        return new Medical(
            $id,
            $medical->getUserId(),
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            null,
            null
        );
    }

    public function update(Medical $medical): Medical
    {
        $query = "UPDATE medicals SET tuition = ?, specialty_id = ? ";
        $query .= "WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            $medical->getId(),
            TenantContext::id()
        ]);
        return $this->find($medical->getId());
    }

    public function find(int $id): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findProjection(int $id): ?MedicalProjection
    {
        $query = "SELECT
                M.*,
                TRIM(CONCAT(U.first_name, ' ', U.last_name)) as medico_nombre,
                U.email,
                U.phone,
                U.is_active AS user_is_active,
                S.name as especialidad_nombre
              FROM medicals M
              INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
              INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
              WHERE M.id = ? AND M.tenant_id = ?";

        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converterprojection->convert($data);
    }



    public function findByUserIdProjection(int $userId): ?MedicalProjection
    {
        $query = "SELECT
                M.*,
                TRIM(CONCAT(U.first_name, ' ', U.last_name)) as medico_nombre,
                U.email,
                U.phone,
                U.is_active AS user_is_active,
                S.name as especialidad_nombre
              FROM medicals M
              INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
              INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
              WHERE M.user_id = ? AND M.tenant_id = ?";

        $result = $this->db->query($query, [$userId, TenantContext::id()]);
        $data = $result->getRow();
        if (!$data) {
            return null;
        }
        return $this->converterprojection->convert($data);
    }

    /**
     * @return MedicalProjection[]
     */
    public function searchAvailableProjection(MedicalFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildAvailableWhereClause($request);

        $query = "SELECT
                    M.id,
                    M.user_id,
                    M.specialty_id,
                    M.tuition,
                    M.created_at,
                    M.updated_at,
                    TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS medico_nombre,
                    U.email,
                    U.phone,
                    U.is_active AS user_is_active,
                    S.name AS especialidad_nombre
                  FROM medicals M
                  INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
                  INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
                  {$whereQuery}
                  ORDER BY U.first_name ASC, U.last_name ASC
                  LIMIT ?, ?";

        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $result = $this->db->query($query, $parameters);
        $projections = [];

        foreach ($result->getResult() as $primitive) {
            $projections[] = $this->converterprojection->convert($primitive);
        }

        return $projections;
    }

    public function countAvailableProjection(MedicalFilterRequest $request): int
    {
        [$whereQuery, $parameters] = $this->buildAvailableWhereClause($request);

        $query = "SELECT COUNT(*) AS total
                  FROM medicals M
                  INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
                  INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
                  {$whereQuery}";

        $result = $this->db->query($query, $parameters);
        return (int) ($result->getRow()->total ?? 0);
    }

    private function buildAvailableWhereClause(MedicalFilterRequest $request): array
    {
        $whereQuery = 'WHERE M.tenant_id = ? AND U.is_active = 1';
        $parameters = [TenantContext::id()];

        if ($request->hasSpecialtyId()) {
            $whereQuery .= ' AND M.specialty_id = ?';
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR S.name LIKE ?)";
            $like = '%' . $request->getSearch() . '%';
            $parameters[] = $like;
            $parameters[] = $like;
        }

        return [$whereQuery, $parameters];
    }

    public function search(MedicalFilterRequest $request): array
    {
        $parameters = [TenantContext::id()];

        $selectQuery = "SELECT M.id, M.user_id, M.tuition, M.specialty_id, M.created_at, M.updated_at ";
        $fromQuery = "FROM medicals M ";
        $whereQuery = "WHERE M.tenant_id = ? ";

        if ($request->hasSpecialtyId()) {
            $whereQuery .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        $orderQuery = "ORDER BY M.id ASC ";

        $paginationQuery = "LIMIT ?, ? ";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $fullQuery = $selectQuery . $fromQuery . $whereQuery . $orderQuery . $paginationQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $primitives = $result->getResult();

        $entities = [];
        foreach ($primitives as $primitive) {
            $entities[] = $this->converter->convert($primitive);
        }

        return $entities;
    }

    public function searchProjection(MedicalFilterRequest $request): array
    {

        $parameters = [TenantContext::id()];
        $selectQuery = "SELECT
                        M.id,
                        M.user_id,
                        M.tuition,
                        M.specialty_id,
                        M.created_at,
                        M.updated_at,

                        TRIM(CONCAT(U.first_name, ' ', U.last_name)) as medico_nombre,
                        U.email,
                        U.phone,
                        U.is_active AS user_is_active,
                        S.name as especialidad_nombre
                    ";

        $fromQuery = "FROM medicals M
                  INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
                  INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
                  ";

        $whereQuery = "WHERE M.tenant_id = ? ";

        if ($request->hasSpecialtyId()) {
            $whereQuery .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR S.name LIKE ? OR M.tuition LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
        }

        $orderQuery = "ORDER BY U.first_name ASC, U.last_name ASC ";

        $paginationQuery = "LIMIT ?, ? ";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $fullQuery = $selectQuery . $fromQuery . $whereQuery . $orderQuery . $paginationQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $primitives = $result->getResult();

        $entities = [];
        foreach ($primitives as $primitive) {
            $entities[] = $this->converterprojection->convert($primitive);
        }

        return $entities;
    }

    public function countProjection(MedicalFilterRequest $request): int
    {
        $parameters = [TenantContext::id()];

        $query = "SELECT COUNT(*) as total
              FROM medicals M
              INNER JOIN users U ON U.id = M.user_id AND U.tenant_id = M.tenant_id
              INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
              WHERE M.tenant_id = ? ";

        if ($request->hasSpecialtyId()) {
            $query .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasSearch()) {
            $query .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR S.name LIKE ? OR M.tuition LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
        }

        $result = $this->db->query($query, $parameters);
        return (int) ($result->getRow()->total ?? 0);
    }


    public function delete(int $id): void
    {
        $query = "DELETE FROM medicals WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }

    public function findByUserId(int $userId): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE user_id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$userId, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findByTuition(string $tuition): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE tuition = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$tuition, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findBySpecialtyId(int $specialtyId): array
    {
        $query = "SELECT * FROM medicals WHERE specialty_id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$specialtyId, TenantContext::id()]);
        $primitives = $result->getResult();
        $entities = [];

        foreach ($primitives as $primitive) {
            $entities[] = $this->converter->convert($primitive);
        }

        return $entities;
    }

    public function countBySpecialtyId(int $specialtyId): int
    {
        $query = "SELECT COUNT(*) as total FROM medicals WHERE specialty_id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$specialtyId, TenantContext::id()]);
        return (int) $result->getRow()->total;
    }
}

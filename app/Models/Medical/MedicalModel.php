<?php

namespace App\Models\Medical;

use App\Converter\Medical\PrimitiveToMedicalConverter;
use App\Converter\Medical\Projection\PrimitiveToMedicalProjectionConverter;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Entity\Medical\Medical;
use App\Entity\Medical\Projection\MedicalProjection;
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
        $query = "INSERT INTO medicals (user_id, tuition, specialty_id, phone, is_active) values (?,?,?,?,?)";
        $this->db->query($query, [
            $medical->getUserId(),
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            $medical->getPhone(),
            $medical->getIsActive()
        ]);
        $id = $this->db->insertID();
        return new Medical(
            $id,
            $medical->getUserId(),
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            $medical->getPhone(),
            $medical->getIsActive(),
            null,
            null
        );
    }

    public function update(Medical $medical): Medical
    {
        $query = "UPDATE medicals SET tuition = ?, specialty_id = ?, phone = ?, is_active = ? ";
        $query .= "WHERE id = ?";
        $this->db->query($query, [
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            $medical->getPhone(),
            $medical->getIsActive(),
            $medical->getId()
        ]);
        return $this->find($medical->getId());
    }

    public function find(int $id): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE id = ?";
        $result = $this->db->query($query, [$id]);
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
                U.username as medico_nombre,
                U.email,
                S.name as especialidad_nombre
              FROM medicals M
              INNER JOIN users U ON U.id = M.user_id
              INNER JOIN specialties S ON S.id = M.specialty_id
              WHERE M.id = ?";

        $result = $this->db->query($query, [$id]);
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
                U.username as medico_nombre,
                U.email,
                S.name as especialidad_nombre
              FROM medicals M
              INNER JOIN users U ON U.id = M.user_id
              INNER JOIN specialties S ON S.id = M.specialty_id
              WHERE M.user_id = ?";

        $result = $this->db->query($query, [$userId]);
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
                    M.phone,
                    M.is_active,
                    M.created_at,
                    M.updated_at,
                    U.username AS medico_nombre,
                    U.email,
                    S.name AS especialidad_nombre
                  FROM medicals M
                  INNER JOIN users U ON U.id = M.user_id
                  INNER JOIN specialties S ON S.id = M.specialty_id
                  {$whereQuery}
                  ORDER BY U.username ASC
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
                  INNER JOIN users U ON U.id = M.user_id
                  INNER JOIN specialties S ON S.id = M.specialty_id
                  {$whereQuery}";

        $result = $this->db->query($query, $parameters);
        return (int) ($result->getRow()->total ?? 0);
    }

    private function buildAvailableWhereClause(MedicalFilterRequest $request): array
    {
        $whereQuery = 'WHERE M.is_active = 1 AND U.is_active = 1';
        $parameters = [];

        if ($request->hasSpecialtyId()) {
            $whereQuery .= ' AND M.specialty_id = ?';
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasSearch()) {
            $whereQuery .= ' AND (U.username LIKE ? OR S.name LIKE ?)';
            $like = '%' . $request->getSearch() . '%';
            $parameters[] = $like;
            $parameters[] = $like;
        }

        return [$whereQuery, $parameters];
    }

    public function search(MedicalFilterRequest $request): array
    {
        $parameters = [];

        $selectQuery = "SELECT M.id, M.user_id, M.tuition, M.specialty_id, M.phone, M.is_active, M.created_at, M.updated_at ";
        $fromQuery = "FROM medicals M ";
        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasSpecialtyId()) {
            $whereQuery .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasIsActive()) {
            $whereQuery .= " AND M.is_active = ? ";
            $parameters[] = $request->getIsActive();
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

        $parameters = [];
        $selectQuery = "SELECT
                        M.id,
                        M.user_id,
                        M.tuition,
                        M.specialty_id,
                        M.phone,
                        M.is_active,
                        M.created_at,
                        M.updated_at,

                        U.username as medico_nombre,
                        U.email,
                        S.name as especialidad_nombre
                    ";

        $fromQuery = "FROM medicals M 
                  INNER JOIN users U ON U.id = M.user_id
                  INNER JOIN specialties S ON S.id = M.specialty_id
                  ";

        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasSpecialtyId()) {
            $whereQuery .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasIsActive()) {
            $whereQuery .= " AND M.is_active = ? ";
            $parameters[] = $request->getIsActive();
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (U.username LIKE ? OR S.name LIKE ? OR M.tuition LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
        }

        $orderQuery = "ORDER BY U.username ASC ";

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
        $parameters = [];

        $query = "SELECT COUNT(*) as total 
              FROM medicals M 
              INNER JOIN users U ON U.id = M.user_id
              INNER JOIN specialties S ON S.id = M.specialty_id
              WHERE 1 = 1 ";

        if ($request->hasSpecialtyId()) {
            $query .= " AND M.specialty_id = ? ";
            $parameters[] = $request->getSpecialtyId();
        }

        if ($request->hasIsActive()) {
            $query .= " AND M.is_active = ? ";
            $parameters[] = $request->getIsActive() ? 1 : 0;
        }

        if ($request->hasSearch()) {
            $query .= " AND (U.username LIKE ? OR S.name LIKE ? OR M.tuition LIKE ?) ";
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
        $query = "DELETE FROM medicals WHERE id = ?";
        $this->db->query($query, [$id]);
    }

    public function findByUserId(int $userId): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE user_id = ?";
        $result = $this->db->query($query, [$userId]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findByTuition(string $tuition): ?Medical
    {
        $query = "SELECT * FROM medicals WHERE tuition = ?";
        $result = $this->db->query($query, [$tuition]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findBySpecialtyId(int $specialtyId): array
    {
        $query = "SELECT * FROM medicals WHERE specialty_id = ?";
        $result = $this->db->query($query, [$specialtyId]);
        $primitives = $result->getResult();
        $entities = [];

        foreach ($primitives as $primitive) {
            $entities[] = $this->converter->convert($primitive);
        }

        return $entities;
    }

    public function countBySpecialtyId(int $specialtyId): int
    {
        $query = "SELECT COUNT(*) as total FROM medicals WHERE specialty_id = ?";
        $result = $this->db->query($query, [$specialtyId]);
        return (int) $result->getRow()->total;
    }
}

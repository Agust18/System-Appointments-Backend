<?php

namespace App\Models\Patient;

use App\Converter\Patient\PrimitiveToPatientConverter;
use App\Converter\Patient\Projection\PrimitiveToPatientProjectionConverter;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Entity\Patient\Patient;
use App\Entity\Patient\Projection\PatientProjection;
use CodeIgniter\Database\BaseConnection;
use Config\Database;


final class PatientModel
{
    private BaseConnection $db;

    private PrimitiveToPatientConverter $converter;
    private PrimitiveToPatientProjectionConverter $converterProjection;

    public function __construct()
    {
        $this->converter = new PrimitiveToPatientConverter();
        $this->db = Database::connect();
        $this->converterProjection = new PrimitiveToPatientProjectionConverter();
    }

    public function insert(Patient $patient): Patient
    {
        $query = "INSERT INTO patients (user_id, birth_date,created_at,updated_at) values (?,?,?,?)";

        $this->db->query($query, [
            $patient->getUserId(),
            $patient->getBirthDate()->format('Y-m-d'),
            $patient->getCreatedAt()->format('Y-m-d H:i:s'),
            $patient->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = $this->db->insertID();

        return new Patient(
            $id,
            $patient->getUserId(),
            $patient->getBirthDate(),
            $patient->getCreatedAt(),
            $patient->getUpdatedAt()
        );
    }

    public function update(Patient $patient): Patient
    {
        $query = "UPDATE patients SET birth_date = ?, updated_at = ? ";
        $query .= "WHERE id = ?";
        $this->db->query($query, [
            $patient->getBirthDate()->format('Y-m-d'),
            $patient->getUpdatedAt()->format('Y-m-d H:i:s'),
            $patient->getId()
        ]);
        return $this->find($patient->getId());
    }


    public function find(int $id): ?Patient
    {
        $query = "SELECT * FROM patients WHERE id = ?";
        $result = $this->db->query($query, [$id]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findByUserId(int $userId): ?Patient
    {
        $query = "SELECT * FROM patients WHERE user_id = ?";
        $result = $this->db->query($query, [$userId]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findProjection(int $id): ?PatientProjection
    {
        $query = "SELECT 
                P.*,
                U.username as paciente_nombre,
                U.dni  
              FROM patients P
              INNER JOIN users U ON U.id = P.user_id
              WHERE P.id = ?";

        $result = $this->db->query($query, [$id]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converterProjection->convert($data);
    }


    public function findByUserIdProjection(int $userId): ?PatientProjection
    {
        $query = "SELECT 
                P.*,
                U.username as paciente_nombre,
                U.dni  
              FROM patients P
              INNER JOIN users U ON U.id = P.user_id
              WHERE P.user_id = ?";

        $result = $this->db->query($query, [$userId]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converterProjection->convert($data);
    }

    public function search(PatientFilterRequest $request): array
    {
        $parameters = [];

        $selectQuery = "SELECT P.id, P.user_id, P.birth_date, P.created_at, P.updated_at ";
        $fromQuery = "FROM patients P ";
        $whereQuery = "WHERE 1 = 1 ";

        $orderQuery = "ORDER BY P.id ASC ";

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

    public function searchProjection(PatientFilterRequest $request): array
    {
        $parameters = [];

        $selectQuery = "SELECT 
                        P.id, 
                        P.user_id, 
                        P.birth_date,  
                        P.created_at, 
                        P.updated_at,
                        U.username as paciente_nombre,
                        U.dni  
                    ";

        $fromQuery = "FROM patients P 
                  INNER JOIN users U ON U.id = P.user_id
                  ";

        $whereQuery = "WHERE 1 = 1 ";


        if ($request->hasSearch()) {
            $whereQuery .= " AND (U.username LIKE ? OR U.dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
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
            $entities[] = $this->converterProjection->convert($primitive);
        }

        return $entities;
    }

    public function countProjection(PatientFilterRequest $request): int
    {
        $parameters = [];

        $query = "SELECT COUNT(*) as total 
              FROM patients P 
              INNER JOIN users U ON U.id = P.user_id 
              WHERE 1 = 1 ";


        if ($request->hasSearch()) {
            $query .= " AND (U.username LIKE ? OR U.dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM patients WHERE id = ?";
        $this->db->query($query, [$id]);
    }
}

<?php

namespace App\Models\Patient;

use App\Converter\Patient\PrimitiveToPatientConverter;
use App\Converter\Patient\Projection\PrimitiveToPatientProjectionConverter;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Entity\Patient\Patient;
use App\Entity\Patient\Projection\PatientProjection;
use App\Exception\Patient\PatientCannotBeCreatedException;
use App\Tenancy\TenantContext;
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
        $query = "INSERT INTO patients (tenant_id, user_id, birth_date,created_at,updated_at) values (?,?,?,?,?)";

        $this->db->query($query, [
            TenantContext::id(),
            $patient->getUserId(),
            $patient->getBirthDate()?->format('Y-m-d'),
            $patient->getCreatedAt()->format('Y-m-d H:i:s'),
            $patient->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = $this->db->insertID();

        // Dentro de una transacción CI4 no lanza si el INSERT falla (p. ej. UNIQUE de user_id):
        // insertID() queda en 0 y devolver un Patient con id 0 ocultaría el error.
        if (!$id) {
            throw new PatientCannotBeCreatedException();
        }

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
        $query .= "WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [
            $patient->getBirthDate()?->format('Y-m-d'),
            $patient->getUpdatedAt()->format('Y-m-d H:i:s'),
            $patient->getId(),
            TenantContext::id()
        ]);
        return $this->find($patient->getId());
    }


    public function find(int $id): ?Patient
    {
        $query = "SELECT * FROM patients WHERE id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findByUserId(int $userId): ?Patient
    {
        $query = "SELECT * FROM patients WHERE user_id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$userId, TenantContext::id()]);
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
                TRIM(CONCAT(U.first_name, ' ', U.last_name)) as paciente_nombre,
                U.dni,
                U.email,
                U.phone
              FROM patients P
              INNER JOIN users U ON U.id = P.user_id AND U.tenant_id = P.tenant_id
              WHERE P.id = ? AND P.tenant_id = ?";

        $result = $this->db->query($query, [$id, TenantContext::id()]);
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
                TRIM(CONCAT(U.first_name, ' ', U.last_name)) as paciente_nombre,
                U.dni,
                U.email,
                U.phone
              FROM patients P
              INNER JOIN users U ON U.id = P.user_id AND U.tenant_id = P.tenant_id
              WHERE P.user_id = ? AND P.tenant_id = ?";

        $result = $this->db->query($query, [$userId, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converterProjection->convert($data);
    }

    public function search(PatientFilterRequest $request): array
    {
        $parameters = [TenantContext::id()];

        $selectQuery = "SELECT P.id, P.user_id, P.birth_date, P.created_at, P.updated_at ";
        $fromQuery = "FROM patients P ";
        $whereQuery = "WHERE P.tenant_id = ? ";

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
        $parameters = [TenantContext::id()];

        $selectQuery = "SELECT
                        P.id,
                        P.user_id,
                        P.birth_date,
                        P.created_at,
                        P.updated_at,
                        TRIM(CONCAT(U.first_name, ' ', U.last_name)) as paciente_nombre,
                        U.dni,
                        U.email,
                        U.phone
                    ";

        $fromQuery = "FROM patients P
                  INNER JOIN users U ON U.id = P.user_id AND U.tenant_id = P.tenant_id
                  ";

        $whereQuery = "WHERE P.tenant_id = ? ";


        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR U.dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($request->hasMedicalUserId()) {
            $whereQuery .= " AND EXISTS (
                SELECT 1 FROM appointments A
                INNER JOIN medicals M ON M.id = A.medical_id AND M.tenant_id = P.tenant_id
                WHERE A.patient_id = P.id AND A.tenant_id = P.tenant_id AND M.user_id = ?
            ) ";
            $parameters[] = $request->getMedicalUserId();
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
            $entities[] = $this->converterProjection->convert($primitive);
        }

        return $entities;
    }

    public function countProjection(PatientFilterRequest $request): int
    {
        $parameters = [TenantContext::id()];

        $query = "SELECT COUNT(*) as total
              FROM patients P
              INNER JOIN users U ON U.id = P.user_id AND U.tenant_id = P.tenant_id
              WHERE P.tenant_id = ? ";


        if ($request->hasSearch()) {
            $query .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR U.dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($request->hasMedicalUserId()) {
            $query .= " AND EXISTS (
                SELECT 1 FROM appointments A
                INNER JOIN medicals M ON M.id = A.medical_id AND M.tenant_id = P.tenant_id
                WHERE A.patient_id = P.id AND A.tenant_id = P.tenant_id AND M.user_id = ?
            ) ";
            $parameters[] = $request->getMedicalUserId();
        }

        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM patients WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }
}

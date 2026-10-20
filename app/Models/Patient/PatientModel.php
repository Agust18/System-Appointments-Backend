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
    private const PROJECTION_SELECT = "SELECT
                P.id,
                P.birth_date,
                P.created_at,
                P.updated_at,
                TRIM(CONCAT(P.first_name, ' ', P.last_name)) AS paciente_nombre,
                P.dni,
                P.email,
                P.phone ";

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
        $query = "INSERT INTO patients (tenant_id, first_name, last_name, email, dni, phone, birth_date, created_at, updated_at) values (?,?,?,?,?,?,?,?,?)";

        $this->db->query($query, [
            TenantContext::id(),
            $patient->getFirstName(),
            $patient->getLastName(),
            $patient->getEmail(),
            $patient->getDni(),
            $patient->getPhone(),
            $patient->getBirthDate()?->format('Y-m-d'),
            $patient->getCreatedAt()->format('Y-m-d H:i:s'),
            $patient->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = $this->db->insertID();

        // Dentro de una transacción CI4 no lanza si el INSERT falla (p. ej. UNIQUE de dni):
        // insertID() queda en 0 y devolver un Patient con id 0 ocultaría el error.
        if (!$id) {
            throw new PatientCannotBeCreatedException();
        }

        return $this->find($id);
    }

    public function update(Patient $patient): Patient
    {
        $query = "UPDATE patients SET first_name = ?, last_name = ?, email = ?, phone = ?, birth_date = ?, updated_at = ? ";
        $query .= "WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [
            $patient->getFirstName(),
            $patient->getLastName(),
            $patient->getEmail(),
            $patient->getPhone(),
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

    public function findByDni(int $dni): ?Patient
    {
        $query = "SELECT * FROM patients WHERE dni = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$dni, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findProjection(int $id): ?PatientProjection
    {
        $query = self::PROJECTION_SELECT . "FROM patients P WHERE P.id = ? AND P.tenant_id = ?";

        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converterProjection->convert($data);
    }

    public function search(PatientFilterRequest $request): array
    {
        $parameters = [TenantContext::id()];

        $selectQuery = "SELECT P.* ";
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

        $fromQuery = "FROM patients P ";
        $whereQuery = "WHERE P.tenant_id = ? ";

        $this->appendFilters($request, $whereQuery, $parameters);

        $orderQuery = "ORDER BY P.first_name ASC, P.last_name ASC ";

        $paginationQuery = "LIMIT ?, ? ";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $fullQuery = self::PROJECTION_SELECT . $fromQuery . $whereQuery . $orderQuery . $paginationQuery;

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

        $query = "SELECT COUNT(*) as total FROM patients P WHERE P.tenant_id = ? ";

        $this->appendFilters($request, $query, $parameters);

        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM patients WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }

    private function appendFilters(PatientFilterRequest $request, string &$whereQuery, array &$parameters): void
    {
        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(P.first_name, ' ', P.last_name) LIKE ? OR P.dni LIKE ?) ";
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
    }
}

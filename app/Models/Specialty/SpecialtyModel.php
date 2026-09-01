<?php

namespace App\Models\Specialty;

use App\Converter\Specialty\PrimitiveToSpecialtyConverter;
use App\Dto\Request\Specialty\SpecialtyFilterRequest;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Entity\Specialty\Specialty;

final class SpecialtyModel
{
    private BaseConnection $db;
    private PrimitiveToSpecialtyConverter $converter;
    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToSpecialtyConverter();
    }

    public function insert(Specialty $specialty): Specialty
    {
        $query = "INSERT INTO specialties (name, description, created_at, updated_at) values (?,?,?,?)";

        $this->db->query($query, [
            $specialty->getName(),
            $specialty->getDescription(),
            $specialty->getCreatedAt()->format('Y-m-d H:i:s'),
            $specialty->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = $this->db->insertID();

        return new Specialty(
            $id,
            $specialty->getName(),
            $specialty->getDescription(),
            $specialty->getCreatedAt(),
            $specialty->getUpdatedAt()
        );
    }

    public function update(Specialty $specialty): Specialty
    {
        $query = "UPDATE specialties SET name = ?, description = ?, updated_at = ? ";
        $query .= "WHERE id = ?";

        $this->db->query($query, [
            $specialty->getName(),
            $specialty->getDescription(),
            $specialty->getUpdatedAt()->format('Y-m-d H:i:s'),
            $specialty->getId()
        ]);

        return $this->find($specialty->getId());
    }

    public function find(int $id): ?Specialty
    {
        $query = "SELECT * FROM specialties WHERE id = ?";
        $result = $this->db->query($query, [$id]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    public function findByName(string $name): ?Specialty
    {
        $query = "SELECT * FROM specialties WHERE name = ?";
        $result = $this->db->query($query, [$name]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }


    public function search(SpecialtyFilterRequest $request): array
    {
        $parameters = [];

        $selectQuery = "SELECT S.id, S.name, S.description, S.created_at, S.updated_at ";
        $fromQuery = "FROM specialties S ";
        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasSearch()) {
            $whereQuery .= " AND (S.name LIKE ? OR S.description LIKE ?) ";
            $term = "%" . $request->getSearch() . "%";
            $parameters[] = $term;
            $parameters[] = $term;
        }

        $orderQuery = "ORDER BY S.id ASC ";

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

    public function count(SpecialtyFilterRequest $request): int
    {
        $parameters = [];

        $selectQuery = "SELECT COUNT(*) as total ";
        $fromQuery = "FROM specialties S ";
        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasSearch()) {
            $whereQuery .= " AND (S.name LIKE ? OR S.description LIKE ?) ";
            $term = "%" . $request->getSearch() . "%";
            $parameters[] = $term;
            $parameters[] = $term;
        }

        $fullQuery = $selectQuery . $fromQuery . $whereQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $row = $result->getRow();

        return (int) $row->total;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM specialties WHERE id = ?";
        $this->db->query($query, [$id]);
    }
}

<?php
namespace App\Models\Role;

use App\Dto\Request\Role\RoleFilterRequest;
use App\Entity\Role\Role;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Constants\Role as RoleConstants;
use App\Converter\Role\PrimitiveToRoleConverter;


final class RoleModel
{
    private BaseConnection $db;
    private PrimitiveToRoleConverter $converter;


    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToRoleConverter();

    }

    public function insert(Role $role): Role
    {
        $query = "INSERT INTO roles (name) VALUES (?)";
        $this->db->query($query, [$role->getName()]);

        $id = $this->db->insertID();

        return $this->find($id);
    }

    public function update(Role $role): Role
    {
        $query = "UPDATE roles SET name = ? WHERE id = ?";
        $this->db->query($query, [$role->getName(), $role->getId()]);

        return $this->find($role->getId());
    }

    public function find(int $id): ?Role
    {
        $query = "SELECT * FROM roles WHERE id = ?";
        $result = $this->db->query($query, [$id]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function findByName(string $name): ?Role
    {
        $query = "SELECT * FROM roles WHERE name = ?";
        $result = $this->db->query($query, [$name]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function search(RoleFilterRequest $request): array
    {
        $parameters = [];
        $query = "SELECT * FROM roles WHERE 1=1 ";

        if ($request->hasSearch()) {
            $query .= " AND name LIKE ? ";
            $parameters[] = "%" . $request->getSearch() . "%";
        }

        $query .= " ORDER BY id ASC ";
        $paginationQuery = "LIMIT ?, ? ";
        $query .= $paginationQuery;

        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();
        
        $result = $this->db->query($query, $parameters);
        $primitives = $result->getResult();

        $entities = [];
        foreach ($primitives as $primitive) {
            $entities[] = $this->converter->convert($primitive);
        }

        return $entities;
    }


    public function count(RoleFilterRequest $request): int
    {
        $parameters = [];

        $selectQuery = "SELECT COUNT(*) as total ";
        $fromQuery = "FROM roles R ";
        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasSearch()) {
            $whereQuery .= " AND R.name LIKE ? ";
            $term = "%" . $request->getSearch() . "%";
            $parameters[] = $term;
    
        }

        $fullQuery = $selectQuery . $fromQuery . $whereQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $row = $result->getRow();

        return (int) $row->total;
    }
    public function delete(int $id): void
    {
        $query = "DELETE FROM roles WHERE id = ?";
        $this->db->query($query, [$id]);
    }


}
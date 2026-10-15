<?php
namespace App\Models\Role;

use App\Converter\Role\PrimitiveToRoleConverter;
use App\Entity\Role\Role;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class RoleModel
{
    private BaseConnection $db;
    private PrimitiveToRoleConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToRoleConverter();
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
}

<?php

namespace App\Models\User;
use App\Constants\Role as RoleConstants;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Entity\User\User;
use App\Converter\User\PrimitiveToUserConverte;
use App\Dto\Request\User\UserFilterRequest;
use DateTime;

final class UserModel
{
    private BaseConnection $db;
    private PrimitiveToUserConverte $converter;

    public function __construct()
    {
        $this->converter = new PrimitiveToUserConverte();
        $this->db = Database::connect();
    }
    public function insert(User $user): User
    {
        $query = "
        INSERT INTO users (
            username,
            email,
            password,
            dni,
            is_active,
            role_id
        )
        VALUES (?,?,?,?,?,?)
    ";

        $this->db->query($query, [
            $user->getUsername(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getDni(),
            $user->getIsActive(),
            $user->getRoleId()
        ]);

        $id = $this->db->insertID();

        return new User(
            $id,
            $user->getUsername(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getDni(),
            $user->getIsActive(),
            null,
            null,
            $user->getRoleId(),
            null,
            null
        );
    }

    public function update(User $user): User
    {
        $query = "UPDATE users SET username = ?, email = ?, password = ?, dni = ?, is_active = ?,role_id = ?,";
        $query .= "token = ?, token_expiration_date = ? ";
        $query .= "WHERE id = ?";
        $this->db->query($query, [
            $user->getUserName(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getDni(),
            $user->getIsActive(),
            $user->getRoleId(),
            $user->getToken(),
            $user->getTokenExpirationDate()?->format("Y-m-d H:i:s"),
            $user->getId()
        ]);
        return $user;
    }

    public function find(int $id): ?User
    {
        $query = "SELECT U.id, U.username, U.email,U.password,U.dni, U.is_active, U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U where U.id = ?";
        $result = $this->db->query($query, [$id]);
        $primitive = $result->getRow();

        if (is_null($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);
        return $user;
    }


    public function findByEmail(string $email): ?User
    {
        $query = "SELECT U.id, U.username, U.email, U.password, U.dni, U.is_active, U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U WHERE U.email = ? ";
        $result = $this->db->query($query, [$email]);

        $primitive = $result->getRow();

        if (empty($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);

        return $user;
    }


    public function findByToken(string $token): ?User
    {
        $now = new DateTime();
        $dateNow = $now->format("Y-m-d H:i:s");

        $query = "SELECT U.id, U.username, U.email, U.password, U.dni,U.is_active,U.token, U.token_expiration_date,role_id,created_at, updated_at ";
        $query .= "FROM users U ";
        $query .= "WHERE U.token = ? AND U.token_expiration_date >= ?";
        $result = $this->db->query($query, [$token, $dateNow]);

        $primitive = $result->getRow();

        if (empty($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);

        return $user;
    }

    /**
     * @return User[]
     */
    public function search(UserFilterRequest $request): array
    {
        $parameters = [];
        
        // $userRoleId = session()->get('roleId');
        
        $selectQuery = "SELECT U.id, U.username, U.email, U.password, U.dni, U.is_active, U.token, U.token_expiration_date, U.role_id, created_at, updated_at ";
        
        $fromQuery = "FROM users U ";
        
        $whereQuery = "WHERE 1 = 1 ";

        // if ($userRoleId === RoleConstants::RECEPTION) {
        //     $whereQuery .= " AND U.role_id IN (?, ?) ";
        //     $parameters[] = RoleConstants::MEDICAL;
        //     $parameters[] = RoleConstants::PATIENT;
        // }

        if ($request->hasEmail()) {
            $whereQuery .= " AND U.email LIKE ? ";
            $parameters[] = "%{$request->getEmail()}%";
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (U.username LIKE ? OR U.dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($request->hasRoleId()) {
            $whereQuery .= " AND U.role_id = ? ";
            $parameters[] = $request->getRoleId();
        }

        if ($request->hasIsActive()) {
            $whereQuery .= " AND U.is_active = ? ";
            $parameters[] = $request->getIsActive();
        }

        $orderQuery = "ORDER BY U.id ASC ";
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

    public function countByFilter(UserFilterRequest $request): int
    {
        $parameters = [];
        // $userRoleId = session()->get('roleId');
        $whereQuery = "WHERE 1 = 1 ";

        //  if ($userRoleId === RoleConstants::RECEPTION) {
        //     $whereQuery .= " AND users.role_id IN (?, ?) ";
        //     $parameters[] = RoleConstants::MEDICAL;
        //     $parameters[] = RoleConstants::PATIENT;
        // }

        if ($request->hasEmail()) {
            $whereQuery .= " AND email LIKE ? ";
            $parameters[] = "%{$request->getEmail()}%";
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (username LIKE ? OR dni LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($request->hasRoleId()) {
            $whereQuery .= " AND role_id = ? ";
            $parameters[] = $request->getRoleId();
        }

        if ($request->hasIsActive()) {
            $whereQuery .= " AND is_active = ? ";
            $parameters[] = $request->getIsActive();
        }

        $query = "SELECT COUNT(*) as total FROM users {$whereQuery}";
        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM users WHERE id = ?";
        $this->db->query($query, [$id]);
    }

    public function countByRoleId(int $roleId): int
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE role_id = ?";
        $result = $this->db->query($query, [$roleId]);
        return $result->getRow()->total;
    }

    public function findByDni(string $dni): ?User
    {
        $query = "SELECT U.id, U.username, U.email, U.password, U.dni, U.is_active, U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U WHERE U.dni = ? ";
        $result = $this->db->query($query, [$dni]);

        $primitive = $result->getRow();

        if (empty($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);

        return $user;
    }


}


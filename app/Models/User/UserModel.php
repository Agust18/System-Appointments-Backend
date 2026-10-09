<?php

namespace App\Models\User;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Entity\User\User;
use App\Converter\User\PrimitiveToUserConverte;
use App\Dto\Request\User\UserFilterRequest;
use App\Exception\User\UserCannotBeCreatedException;
use App\Tenancy\TenantContext;
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
            tenant_id,
            first_name,
            last_name,
            email,
            phone,
            password,
            dni,
            is_active,
            is_verified,
            role_id
        )
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ";

        $this->db->query($query, [
            TenantContext::id(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getPassword(),
            $user->getDni(),
            $user->getIsActive(),
            $user->getIsVerified(),
            $user->getRoleId()
        ]);

        $id = $this->db->insertID();

        if (!$id) {
            throw new UserCannotBeCreatedException();
        }

        $newUser = $this->find($id);

        if ($newUser === null) {
            throw new UserCannotBeCreatedException();
        }

        return $newUser;
    }

    public function update(User $user): User
    {
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ?, dni = ?, is_active = ?, is_verified = ?, role_id = ?,updated_at = NOW(), ";
        $query .= "token = ?, token_expiration_date = ? ";
        $query .= "WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getPassword(),
            $user->getDni(),
            $user->getIsActive(),
            $user->getIsVerified(),
            $user->getRoleId(),
            $user->getToken(),
            $user->getTokenExpirationDate()?->format("Y-m-d H:i:s"),
            $user->getId(),
            TenantContext::id()
        ]);
        return $user;
    }

    public function find(int $id): ?User
    {
        $query = "SELECT U.id, U.first_name, U.last_name, U.email,U.phone, U.password,U.dni, U.is_active,U.is_verified,U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U where U.id = ? AND U.tenant_id = ?";
        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $primitive = $result->getRow();

        if (is_null($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);
        return $user;
    }


    public function findByEmail(string $email): ?User
    {
        $query = "SELECT U.id, U.first_name, U.last_name, U.email, U.phone, U.password, U.dni, U.is_active,U.is_verified, U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U WHERE U.email = ? AND U.tenant_id = ? ";
        $result = $this->db->query($query, [$email, TenantContext::id()]);

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

        $query = "SELECT U.id, U.first_name, U.last_name, U.email, U.phone, U.password, U.dni, U.is_active,U.is_verified, U.token, U.token_expiration_date,role_id,created_at, updated_at ";
        $query .= "FROM users U ";
        $query .= "WHERE U.token = ? AND U.token_expiration_date >= ? AND U.tenant_id = ?";
        $result = $this->db->query($query, [$token, $dateNow, TenantContext::id()]);

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
        $parameters = [TenantContext::id()];

        $selectQuery = "SELECT U.id, U.first_name, U.last_name, U.email, U.phone, U.password, U.dni, U.is_active,U.is_verified, U.token, U.token_expiration_date, U.role_id, created_at, updated_at ";

        $fromQuery = "FROM users U ";

        $whereQuery = "WHERE U.tenant_id = ? ";

        if ($request->hasEmail()) {
            $whereQuery .= " AND U.email LIKE ? ";
            $parameters[] = "%{$request->getEmail()}%";
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR U.dni LIKE ?) ";
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
        $parameters = [TenantContext::id()];

        $fromQuery = "FROM users U ";

        $whereQuery = "WHERE U.tenant_id = ? ";

        if ($request->hasEmail()) {
            $whereQuery .= " AND U.email LIKE ? ";
            $parameters[] = "%{$request->getEmail()}%";
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ? OR U.dni LIKE ?) ";
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

        $query = "SELECT COUNT(*) as total {$fromQuery} {$whereQuery}";
        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM users WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }

    public function markAsVerified(int $userId): void
    {
        $query = "UPDATE users SET is_verified = 1, updated_at = NOW() WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$userId, TenantContext::id()]);
    }

    public function countByRoleId(int $roleId): int
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE role_id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$roleId, TenantContext::id()]);
        return (int) ($result->getRow()->total ?? 0);
    }

    public function findByDni(string $dni): ?User
    {
        $query = "SELECT U.id, U.first_name, U.last_name, U.email, U.phone, U.password, U.dni, U.is_active,U.is_verified,U.token, U.token_expiration_date,role_id,created_at, updated_at FROM users U WHERE U.dni = ? AND U.tenant_id = ? ";
        $result = $this->db->query($query, [$dni, TenantContext::id()]);

        $primitive = $result->getRow();

        if (empty($primitive)) {
            return null;
        }

        $user = $this->converter->convert($primitive);

        return $user;
    }

    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$hashedPassword, $userId, TenantContext::id()]);
    }


}

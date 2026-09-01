<?php
namespace App\Dto\Response\User;

final readonly class UserResponse
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public ?int $dni,
        public bool $isActive,
        public int $roleId,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
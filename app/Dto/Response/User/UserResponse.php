<?php
namespace App\Dto\Response\User;

final readonly class UserResponse
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public ?string $email,
        public ?string $phone,
        public ?int $dni,
        public bool $isActive,
        public int $roleId,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
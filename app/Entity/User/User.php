<?php

namespace App\Entity\User;

use App\Dto\Request\User\UserRequest;
use App\Dto\Request\User\UserUpdaterRequest;
use DateTime;

final class User
{
    public function __construct(
        private ?int $id,
        private string $firstName,
        private string $lastName,
        private string $email,
        private ?string $phone,
        private string $password,
        private int $dni,
        private bool $isActive,
        private bool $isVerified,
        private ?string $token,
        private ?DateTime $tokenExpirationDate,
        private ?int $roleId,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt

    ) {
    }


    public function modify(UserUpdaterRequest $request): void
    {
        $this->firstName = $request->getFirstName() ?? $this->firstName;
        $this->lastName = $request->getLastName() ?? $this->lastName;
        $this->email = $request->getEmail() ?? $this->email;
        $this->phone = $request->getPhone() ?? $this->phone;
        $this->password = $request->getPassword() ? password_hash($request->getPassword(), PASSWORD_BCRYPT) : $this->password;
        $this->dni = $request->getDni() ?? $this->dni;
        $this->isActive = $request->getIsActive() ?? $this->isActive;
        $this->roleId = $request->getRoleId() ?? $this->roleId;
    }

    public function getTokenExpirationDate(): ?DateTime
    {
        return $this->tokenExpirationDate;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function generateToken(): void
    {
        //recomendacion  bin2hex(random_bytes(32));  
        // $this->token = bin2hex(random_bytes(32));   // 64 caracteres aleatorios
        $this->token = md5($this->email . rand(1000, 9999) . date("YmdHis"));
        $this->tokenExpirationDate = new DateTime("+24 hours");
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
   
    public function getPassword(): string
    {
        return $this->password;
    }
    public function getDni(): int
    {
        return $this->dni;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function markAsVerified(): void
    {
        $this->isVerified = true;
    }


    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function convertFromRequest(UserRequest $request): User
    {
        return new User(
            null,
            $request->getFirstName(),
            $request->getLastName(),
            $request->getEmail(),
            $request->getPhone(),
            password_hash($request->getPassword(), PASSWORD_BCRYPT),
            $request->getDni(),
            $request->getIsActive(),
            false,
            null,
            null,
            $request->getRoleId(),
            null,
            null
        );
    }
}

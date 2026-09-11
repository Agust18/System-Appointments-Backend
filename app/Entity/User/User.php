<?php

namespace App\Entity\User;

use App\Dto\Request\User\UserRequest;
use App\Dto\Request\User\UserUpdaterRequest;
use DateTime;

final class User
{
    public function __construct(
        private ?int $id,
        private string $username,
        private string $email,
        private string $password,
        private ?int $dni,
        private bool $isActive,
        private ?string $token,
        private ?DateTime $tokenExpirationDate,
        private ?int $roleId,
        private ?DateTime $createdAt,   
        private ?DateTime $updatedAt

    ) {
    }


    public function modify(UserUpdaterRequest $request): void
    {
        $this->username = $request->getUsername() ?? $this->username;
        $this->email = $request->getEmail() ?? $this->email;
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
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function getDni(): ?int
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
                $request->getUserName(),
                $request->getEmail(),
                password_hash($request->getPassword(), PASSWORD_BCRYPT),
                $request->getDni(),
                $request->getIsActive(),
                null, 
                null, 
                $request->getRoleId(), 
                null, 
                null  
            );
    }
}

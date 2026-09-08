<?php
namespace App\Dto\Request\User;

final readonly class UserUpdaterRequest {
    public function __construct(
        private ?string $username = null,
        private ?string $email = null,
        private ?string $password = null,
        private ?int $dni = null,
        private ?bool $isActive = null,
        private ?int $roleId = null  


    )
    {}
    public function getPassword():?string
    {
        return $this->password;
    }
    public function getDni():?int
    {
        return $this->dni;
    }
    public function getIsActive():?bool
    {
        return $this->isActive;
    }

    public function getEmail():?string
    {
        return $this->email;
    }
    public function getUserName():?string
    {
        return $this->username;
    }

     public function getRoleId(): ?int  
    {
        return $this->roleId;
    }
}

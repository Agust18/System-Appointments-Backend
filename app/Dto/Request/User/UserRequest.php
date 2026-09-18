<?php
namespace App\Dto\Request\User;
final readonly class UserRequest{
    public function __construct(
        private string $username,
        private string $email,
        private string $password,
        private int $dni,
        private bool $isActive, 
        private ?int $roleId 
       
    ){}

    public function getUsername():string
    {
        return $this->username;
    }
    
    public function getEmail():string
    {
        return $this->email;

    }
    public function getPassword():string
    {
        return $this->password;
    }
    public function getDni():int
    {
        return $this->dni;
    }

    public function getRoleId():?int
    {
        return $this->roleId;
    }
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    
    
    
    

}

